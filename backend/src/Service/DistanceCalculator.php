<?php

declare(strict_types=1);

namespace App\Service;

use App\ValueObject\Coordinates;
use App\ValueObject\DistanceAndBearing;

/**
 * Calcul de distance Haversine et azimut initial entre deux points GPS.
 * Utilisé partout : feedback d'essai, génération de grille emoji, tracé sur carte.
 */
final class DistanceCalculator
{
    private const EARTH_RADIUS_KM = 6371.0;

    /** Ordre identique à celui retourné par bearing → secteur de 45°. */
    private const COMPASS = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];

    public function compute(Coordinates $from, Coordinates $to): DistanceAndBearing
    {
        $distanceKm = $this->haversineKm($from, $to);
        $bearing = $this->bearingDegrees($from, $to);

        return new DistanceAndBearing(
            distanceKm: (int) round($distanceKm),
            bearingDegrees: (int) round($bearing) % 360,
            bearingLabel: $this->bearingToCompass($bearing),
        );
    }

    private function haversineKm(Coordinates $from, Coordinates $to): float
    {
        $lat1 = $this->toRad($from->latitude);
        $lat2 = $this->toRad($to->latitude);
        $deltaLat = $this->toRad($to->latitude - $from->latitude);
        $deltaLon = $this->toRad($to->longitude - $from->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Azimut initial (forward azimuth) en degrés [0, 360).
     * 0 = nord géographique, 90 = est, 180 = sud, 270 = ouest.
     */
    private function bearingDegrees(Coordinates $from, Coordinates $to): float
    {
        $lat1 = $this->toRad($from->latitude);
        $lat2 = $this->toRad($to->latitude);
        $deltaLon = $this->toRad($to->longitude - $from->longitude);

        $y = sin($deltaLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLon);
        $bearing = atan2($y, $x);
        $degrees = rad2deg($bearing);

        return fmod($degrees + 360, 360);
    }

    private function bearingToCompass(float $degrees): string
    {
        // Découpe en 8 secteurs de 45°, centrés sur N (NNE..NNW autour de 0°).
        // fmod() évite la conversion float→int implicite dépréciée en PHP 8.4.
        $index = (int) floor(fmod($degrees + 22.5, 360) / 45);

        return self::COMPASS[$index];
    }

    private function toRad(float $deg): float
    {
        return $deg * M_PI / 180.0;
    }
}
