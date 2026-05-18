<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Commune;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commune>
 */
final class CommuneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commune::class);
    }

    public function findOneByCodeInsee(string $codeInsee): ?Commune
    {
        return $this->findOneBy(['codeInsee' => $codeInsee]);
    }

    /**
     * Recherche fuzzy basée sur l'extension PostgreSQL `pg_trgm`.
     * Seuil de similarité 0.25 (cf. brief). La requête utilise SET LOCAL
     * pour ne pas polluer la session connection-wide.
     *
     * Indice GIN sur `name_normalized` créé en migration initiale.
     *
     * @return list<Commune>
     */
    public function searchByFuzzyName(string $normalizedQuery, int $limit = 8): array
    {
        if ('' === trim($normalizedQuery)) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement('SET LOCAL pg_trgm.similarity_threshold = 0.25');

        $sql = <<<'SQL'
                SELECT id
                  FROM commune
                 WHERE name_normalized % :q
              ORDER BY similarity(name_normalized, :q) DESC,
                       population DESC
                 LIMIT :lim
            SQL;

        $rows = $conn->executeQuery($sql, ['q' => $normalizedQuery, 'lim' => $limit])->fetchAllAssociative();
        if ([] === $rows) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $byId = [];
        foreach ($this->findBy(['id' => $ids]) as $commune) {
            $byId[(string) $commune->getId()] = $commune;
        }

        // Conserve l'ordre de pertinence renvoyé par PostgreSQL.
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }
}
