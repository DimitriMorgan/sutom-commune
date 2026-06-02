<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyPuzzle>
 */
final class DailyPuzzleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyPuzzle::class);
    }

    public function findOneByDate(\DateTimeImmutable $date): ?DailyPuzzle
    {
        return $this->findOneBy(['puzzleDate' => $date->setTime(0, 0)]);
    }

    public function nextPuzzleNumber(): int
    {
        $max = (int) $this->createQueryBuilder('p')
            ->select('COALESCE(MAX(p.puzzleNumber), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return $max + 1;
    }

    /**
     * @return list<string> codes INSEE des communes utilisées entre $from et $to (inclus)
     */
    public function communeCodesUsedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<array{codeInsee: string}> $rows */
        $rows = $this->createQueryBuilder('p')
            ->select('c.codeInsee AS codeInsee')
            ->join('p.commune', 'c')
            ->where('p.puzzleDate BETWEEN :from AND :to')
            ->setParameter('from', $from->setTime(0, 0))
            ->setParameter('to', $to->setTime(0, 0))
            ->getQuery()
            ->getArrayResult();

        return array_values(array_unique(array_map(static fn (array $r): string => $r['codeInsee'], $rows)));
    }

    /**
     * Apparitions PASSÉES d'une commune comme énigme (date < aujourd'hui strictement).
     *
     * Anti-spoil : on n'inclut JAMAIS l'énigme du jour ni une future. Si la commune
     * est la cible d'aujourd'hui, la page /commune/{slug} ne doit pas le trahir — seules
     * les apparitions déjà closes sont listées.
     *
     * @return list<DailyPuzzle>
     */
    public function findPastAppearancesForCommune(Commune $commune, \DateTimeImmutable $today): array
    {
        /* @var list<DailyPuzzle> */
        return $this->createQueryBuilder('p')
            ->where('p.commune = :commune')
            ->andWhere('p.puzzleDate < :today')
            ->orderBy('p.puzzleDate', 'DESC')
            ->setParameter('commune', $commune)
            ->setParameter('today', $today->setTime(0, 0))
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste les énigmes passées ou actuelles (date ≤ aujourd'hui) entre deux dates, ordonnées DESC.
     *
     * @return list<DailyPuzzle>
     */
    public function findArchive(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /* @var list<DailyPuzzle> */
        return $this->createQueryBuilder('p')
            ->where('p.puzzleDate BETWEEN :from AND :to')
            ->orderBy('p.puzzleDate', 'DESC')
            ->setParameter('from', $from->setTime(0, 0))
            ->setParameter('to', $to->setTime(0, 0))
            ->getQuery()
            ->getResult();
    }
}
