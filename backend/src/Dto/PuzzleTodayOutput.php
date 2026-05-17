<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Vue publique de l'énigme du jour. ATTENTION : chaque champ est whitelisté ici.
 * Ne JAMAIS ajouter latitude, longitude, name, codeInsee, population, patrimoine,
 * specialite, climate ou wikipediaUrl tant que le jeu n'est pas terminé.
 *
 * Tout ajout doit faire passer (et idéalement enrichir) AntiCheatTest.
 */
final readonly class PuzzleTodayOutput
{
    public function __construct(
        public int $puzzleNumber,
        public string $puzzleDate,
        public string $regionName,
        public string $departmentName,
        public string $departmentCode,
        public ?string $theme,
        public int $maxAttempts,
    ) {
    }
}
