<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProximityTierResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProximityTierResolverTest extends TestCase
{
    /**
     * @return list<array{0: int, 1: string}>
     */
    public static function cases(): array
    {
        return [
            [0,    ProximityTierResolver::TIER_GREEN],
            [5,    ProximityTierResolver::TIER_GREEN],
            [6,    ProximityTierResolver::TIER_YELLOW],
            [25,   ProximityTierResolver::TIER_YELLOW],
            [26,   ProximityTierResolver::TIER_ORANGE],
            [100,  ProximityTierResolver::TIER_ORANGE],
            [101,  ProximityTierResolver::TIER_RED],
            [800,  ProximityTierResolver::TIER_RED],
            [9999, ProximityTierResolver::TIER_RED],
        ];
    }

    #[DataProvider('cases')]
    public function testFromKilometers(int $km, string $expected): void
    {
        $resolver = new ProximityTierResolver();

        self::assertSame($expected, $resolver->fromKilometers($km));
    }
}
