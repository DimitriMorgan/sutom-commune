<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Réponse polymorphique de POST /api/puzzle/guess.
 * Champs présents/absents selon `gameOver` :
 *  - false : isCorrect=false, distanceKm/bearing/proximityTier/nextHint/attemptsRemaining
 *  - true  : isCorrect=true|false, solution, guessPath, shareGrid.
 */
final readonly class PuzzleGuessOutput
{
    /**
     * @param array{key: string, label: string, value: string}|null $nextHint
     * @param list<GuessPathStep>|null                              $guessPath
     */
    public function __construct(
        public bool $isCorrect,
        public bool $gameOver,
        public ?int $distanceKm = null,
        public ?string $bearing = null,
        public ?int $bearingDegrees = null,
        public ?string $proximityTier = null,
        public ?array $nextHint = null,
        public ?int $attemptsRemaining = null,
        // Coordonnées de la commune PROPOSÉE (jamais celles de la cible) pour le pin
        // sur la carte côté client. Sans danger anti-cheat : l'utilisateur les a déjà
        // implicitement en proposant la commune.
        public ?float $guessLatitude = null,
        public ?float $guessLongitude = null,
        public ?string $guessName = null,
        public ?string $guessCodeInsee = null,
        public ?PuzzleSolution $solution = null,
        public ?array $guessPath = null,
        public ?string $shareGrid = null,
    ) {
    }
}
