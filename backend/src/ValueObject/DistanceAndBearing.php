<?php

declare(strict_types=1);

namespace App\ValueObject;

final readonly class DistanceAndBearing
{
    /**
     * @param int    $distanceKm     distance arrondie au km le plus proche
     * @param int    $bearingDegrees azimut initial 0..359 (0 = nord, 90 = est)
     * @param string $bearingLabel   N/NE/E/SE/S/SW/W/NW
     */
    public function __construct(
        public int $distanceKm,
        public int $bearingDegrees,
        public string $bearingLabel,
    ) {
    }
}
