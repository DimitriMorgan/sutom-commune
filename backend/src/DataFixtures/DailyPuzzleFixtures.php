<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Repository\CommuneRepository;
use App\Service\Clock;
use App\Service\DailyPuzzleSelector;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Génère 30 énigmes rétroactives (aujourd'hui inclus) pour que l'archive ne soit pas vide en dev.
 * S'appuie sur DailyPuzzleSelector pour respecter les règles : exclusion 365 jours, difficulté par jour.
 */
final class DailyPuzzleFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly DailyPuzzleSelector $selector,
        private readonly Clock $clock,
        private readonly CommuneRepository $communeRepo,
    ) {
    }

    public function getDependencies(): array
    {
        return [CommuneFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        // Pas de commune en base → rien à faire.
        if (0 === \count($this->communeRepo->findBy(['isPlayable' => true], null, 1))) {
            return;
        }

        $today = $this->clock->today();
        for ($i = 29; $i >= 0; --$i) {
            $date = $today->modify("-{$i} days");
            try {
                $this->selector->selectForDate($date);
            } catch (\RuntimeException) {
                // pas assez de candidates pour ce bucket — on continue
            }
        }
    }
}
