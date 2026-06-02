<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;

/**
 * Fiche encyclopédique publique d'une commune jouable (page longue traîne SEO).
 *
 * Ne contient que des faits publics (géo, démographie, patrimoine). Les
 * `pastAppearances` listent uniquement les énigmes DÉJÀ closes — jamais celle
 * du jour ni une future (anti-spoil, cf. DailyPuzzleRepository).
 */
final readonly class CommuneDetail
{
    /**
     * @param list<string>                                 $postalCodes
     * @param list<string>                                 $patrimoine
     * @param list<array{date: string, puzzleNumber: int}> $pastAppearances
     */
    public function __construct(
        public string $slug,
        public string $codeInsee,
        public string $name,
        public array $postalCodes,
        public string $departmentName,
        public string $departmentCode,
        public string $regionName,
        public string $regionCode,
        public float $latitude,
        public float $longitude,
        public int $population,
        public ?int $altitude,
        public string $climate,
        public array $patrimoine,
        public ?string $specialite,
        public ?string $difficulty,
        public ?string $wikipediaUrl,
        public array $pastAppearances,
    ) {
    }

    /**
     * @param list<DailyPuzzle> $pastAppearances
     */
    public static function fromEntity(Commune $commune, array $pastAppearances): self
    {
        return new self(
            slug: $commune->getNameSlug(),
            codeInsee: $commune->getCodeInsee(),
            name: $commune->getName(),
            postalCodes: $commune->getPostalCodes(),
            departmentName: $commune->getDepartmentName(),
            departmentCode: $commune->getDepartmentCode(),
            regionName: $commune->getRegionName(),
            regionCode: $commune->getRegionCode(),
            latitude: (float) $commune->getLatitude(),
            longitude: (float) $commune->getLongitude(),
            population: $commune->getPopulation(),
            altitude: $commune->getAltitude(),
            climate: $commune->getClimate()->label(),
            patrimoine: $commune->getPatrimoine(),
            specialite: $commune->getSpecialite(),
            difficulty: $commune->getDifficulty()?->value,
            wikipediaUrl: $commune->getWikipediaUrl(),
            pastAppearances: array_map(
                static fn (DailyPuzzle $p): array => [
                    'date' => $p->getPuzzleDate()->format('Y-m-d'),
                    'puzzleNumber' => $p->getPuzzleNumber(),
                ],
                $pastAppearances,
            ),
        );
    }
}
