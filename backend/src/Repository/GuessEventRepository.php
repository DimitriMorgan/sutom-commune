<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DailyPuzzle;
use App\Entity\GuessEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuessEvent>
 */
final class GuessEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuessEvent::class);
    }

    /**
     * @return list<GuessEvent>
     */
    public function findForSession(DailyPuzzle $puzzle, string $sessionHash): array
    {
        /* @var list<GuessEvent> */
        return $this->createQueryBuilder('e')
            ->where('e.puzzle = :p AND e.sessionHash = :s')
            ->orderBy('e.attemptIndex', 'ASC')
            ->setParameter('p', $puzzle)
            ->setParameter('s', $sessionHash)
            ->getQuery()
            ->getResult();
    }

    public function countSessionsForPuzzle(DailyPuzzle $puzzle): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.sessionHash)')
            ->where('e.puzzle = :p')
            ->setParameter('p', $puzzle)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
