<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Convertit une distance en tier coloré pour la grille emoji et le badge GuessRow.
 *
 * Seuils (cf. brief) :
 *  - green  ≤ 5 km
 *  - yellow ≤ 25 km
 *  - orange ≤ 100 km
 *  - red    > 100 km
 */
final class ProximityTierResolver
{
    public const TIER_GREEN = 'green';
    public const TIER_YELLOW = 'yellow';
    public const TIER_ORANGE = 'orange';
    public const TIER_RED = 'red';

    public function fromKilometers(int $km): string
    {
        return match (true) {
            $km <= 5 => self::TIER_GREEN,
            $km <= 25 => self::TIER_YELLOW,
            $km <= 100 => self::TIER_ORANGE,
            default => self::TIER_RED,
        };
    }
}
