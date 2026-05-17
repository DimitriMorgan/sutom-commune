<?php

declare(strict_types=1);

namespace App\Enum;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
    case Expert = 'expert';

    /**
     * Buckets définis dans le brief :
     * - easy : ≥ 50 000 habitants
     * - medium : 15 000 à 50 000
     * - hard : 8 000 à 15 000
     * - expert : 5 000 à 8 000
     * En dessous de 5 000 la commune n'est pas jouable (retourne null).
     */
    public static function fromPopulation(int $population): ?self
    {
        return match (true) {
            $population >= 50_000 => self::Easy,
            $population >= 15_000 => self::Medium,
            $population >= 8_000 => self::Hard,
            $population >= 5_000 => self::Expert,
            default => null,
        };
    }
}
