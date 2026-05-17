<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class GuessPathStep
{
    public function __construct(
        public string $codeInsee,
        public string $name,
        public float $latitude,
        public float $longitude,
        public int $distanceKm,
        public string $bearing,
        public int $bearingDegrees,
        public string $proximityTier,
        public bool $isCorrect,
    ) {
    }
}
