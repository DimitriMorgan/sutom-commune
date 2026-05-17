<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DistanceCalculator;
use App\ValueObject\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DistanceCalculatorTest extends TestCase
{
    /**
     * Distances de référence (vérifiées sur Google Maps) :
     *
     * @return list<array{0: string, 1: float, 2: float, 3: float, 4: float, 5: int}>
     */
    public static function distanceCases(): array
    {
        return [
            // 166 km à vol d'oiseau (le brief évoque 198 km, mais c'est la distance routière)
            ['Paris ↔ Honfleur', 48.8566, 2.3522, 49.4197, 0.2360, 166],
            ['Paris ↔ Marseille', 48.8566, 2.3522, 43.2965, 5.3698, 661],
            ['Paris ↔ Lyon', 48.8566, 2.3522, 45.7640, 4.8357, 392],
            ['Paris ↔ Brest', 48.8566, 2.3522, 48.3904, -4.4861, 502],
            ['Paris ↔ Strasbourg', 48.8566, 2.3522, 48.5734, 7.7521, 397],
            ['Lyon ↔ Marseille', 45.7640, 4.8357, 43.2965, 5.3698, 277],
            ['Bordeaux ↔ Toulouse', 44.8378, -0.5792, 43.6047, 1.4442, 211],
            ['Lille ↔ Marseille', 50.6292, 3.0573, 43.2965, 5.3698, 836],
            ['Nice ↔ Strasbourg', 43.7102, 7.2620, 48.5734, 7.7521, 542],
            ['Paris ↔ Paris', 48.8566, 2.3522, 48.8566, 2.3522, 0],
        ];
    }

    #[DataProvider('distanceCases')]
    public function testHaversineDistance(string $label, float $lat1, float $lon1, float $lat2, float $lon2, int $expectedKm): void
    {
        $calc = new DistanceCalculator();
        $result = $calc->compute(new Coordinates($lat1, $lon1), new Coordinates($lat2, $lon2));

        self::assertEqualsWithDelta($expectedKm, $result->distanceKm, 5, $label.' (got '.$result->distanceKm.' km)');
    }

    /**
     * Bearing connus (vérifiés via formule classique) :
     *
     * @return list<array{0: string, 1: float, 2: float, 3: float, 4: float, 5: string}>
     */
    public static function bearingCases(): array
    {
        return [
            // Paris → Lille = ~nord
            ['Paris → Lille', 48.8566, 2.3522, 50.6292, 3.0573, 'N'],
            // Paris → Strasbourg = ~est (légèrement nord-est)
            ['Paris → Strasbourg', 48.8566, 2.3522, 48.5734, 7.7521, 'E'],
            // Paris → Marseille = sud-sud-est, donc S/SE selon arrondi (sud ici)
            ['Paris → Marseille', 48.8566, 2.3522, 43.2965, 5.3698, 'S'],
            // Paris → Brest = ouest
            ['Paris → Brest', 48.8566, 2.3522, 48.3904, -4.4861, 'W'],
            // Marseille → Lille = nord (~352°)
            ['Marseille → Lille', 43.2965, 5.3698, 50.6292, 3.0573, 'N'],
            // Bordeaux → Strasbourg = nord-est
            ['Bordeaux → Strasbourg', 44.8378, -0.5792, 48.5734, 7.7521, 'NE'],
            // Strasbourg → Brest = ouest
            ['Strasbourg → Brest', 48.5734, 7.7521, 48.3904, -4.4861, 'W'],
            // Honfleur → Nice = sud-est
            ['Honfleur → Nice', 49.4197, 0.2360, 43.7102, 7.2620, 'SE'],
        ];
    }

    #[DataProvider('bearingCases')]
    public function testBearingLabel(string $label, float $lat1, float $lon1, float $lat2, float $lon2, string $expected): void
    {
        $calc = new DistanceCalculator();
        $result = $calc->compute(new Coordinates($lat1, $lon1), new Coordinates($lat2, $lon2));

        self::assertSame(
            $expected,
            $result->bearingLabel,
            sprintf('%s : attendu %s, reçu %s (%d°)', $label, $expected, $result->bearingLabel, $result->bearingDegrees),
        );
    }

    public function testBearingDegreesAreInValidRange(): void
    {
        $calc = new DistanceCalculator();
        $result = $calc->compute(new Coordinates(48.8566, 2.3522), new Coordinates(43.2965, 5.3698));

        self::assertGreaterThanOrEqual(0, $result->bearingDegrees);
        self::assertLessThan(360, $result->bearingDegrees);
    }
}
