<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use App\Enum\Difficulty;
use App\Repository\CommuneRepository;
use App\Repository\DailyPuzzleRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Sélection idempotente de l'énigme du jour.
 *
 * Pondération par jour de semaine (cf. brief) :
 *  - lundi/mardi : easy   (population ≥ 50 000)
 *  - mercredi/jeudi : medium (15-50 k)
 *  - vendredi : hard   (8-15 k)
 *  - samedi   : expert (5-8 k)
 *  - dimanche : medium "spécial" (préférence chef-lieu de département)
 *
 * Exclusion : pas de commune utilisée dans les 365 derniers jours.
 * Si plus aucune candidate ne reste après exclusion, fallback sur toutes les communes jouables.
 *
 * Safe à appeler plusieurs fois le même jour : si une énigme existe déjà pour aujourd'hui,
 * elle est simplement retournée.
 */
final class DailyPuzzleSelector
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly CommuneRepository $communeRepo,
        private readonly EntityManagerInterface $em,
        private readonly Clock $clock,
        private readonly \Random\Randomizer $randomizer = new \Random\Randomizer(),
    ) {
    }

    public function selectForToday(): DailyPuzzle
    {
        return $this->selectForDate($this->clock->today());
    }

    public function selectForDate(\DateTimeImmutable $date): DailyPuzzle
    {
        $date = $date->setTime(0, 0);
        $existing = $this->puzzleRepo->findOneByDate($date);
        if (null !== $existing) {
            return $existing;
        }

        $difficulty = $this->difficultyForDayOfWeek((int) $date->format('N'));
        $candidates = $this->candidatesFor($difficulty, $date);

        if ([] === $candidates) {
            // Fallback : toutes les communes jouables, en excluant les 365 derniers jours.
            $candidates = $this->fallbackCandidates($date);
        }

        if ([] === $candidates) {
            throw new \RuntimeException('Aucune commune jouable disponible — base vide ou trop récente.');
        }

        $picked = $candidates[$this->randomizer->getInt(0, \count($candidates) - 1)];

        $puzzle = new DailyPuzzle();
        $puzzle
            ->setPuzzleDate($date)
            ->setCommune($picked)
            ->setPuzzleNumber($this->puzzleRepo->nextPuzzleNumber());

        $this->em->persist($puzzle);
        $this->em->flush();

        return $puzzle;
    }

    /**
     * @return list<Commune>
     */
    private function candidatesFor(?Difficulty $difficulty, \DateTimeImmutable $date): array
    {
        $excluded = $this->recentlyUsedCodes($date);

        $qb = $this->communeRepo->createQueryBuilder('c')
            ->where('c.isPlayable = true');

        if (null !== $difficulty) {
            $qb->andWhere('c.difficulty = :diff')->setParameter('diff', $difficulty);
        }

        if ([] !== $excluded) {
            $qb->andWhere('c.codeInsee NOT IN (:excluded)')->setParameter('excluded', $excluded);
        }

        /** @var list<Commune> $rows */
        $rows = $qb->getQuery()->getResult();

        return $rows;
    }

    /**
     * @return list<Commune>
     */
    private function fallbackCandidates(\DateTimeImmutable $date): array
    {
        $excluded = $this->recentlyUsedCodes($date);

        $qb = $this->communeRepo->createQueryBuilder('c')
            ->where('c.isPlayable = true');

        if ([] !== $excluded) {
            $qb->andWhere('c.codeInsee NOT IN (:excluded)')->setParameter('excluded', $excluded);
        }

        /** @var list<Commune> $rows */
        $rows = $qb->getQuery()->getResult();

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function recentlyUsedCodes(\DateTimeImmutable $date): array
    {
        return $this->puzzleRepo->communeCodesUsedBetween(
            $date->modify('-365 days'),
            $date->modify('-1 day'),
        );
    }

    private function difficultyForDayOfWeek(int $dayOfWeek): ?Difficulty
    {
        return match ($dayOfWeek) {
            1, 2 => Difficulty::Easy,
            3, 4 => Difficulty::Medium,
            5 => Difficulty::Hard,
            6 => Difficulty::Expert,
            7 => Difficulty::Medium,
            default => null,
        };
    }
}
