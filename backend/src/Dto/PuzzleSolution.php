<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class PuzzleSolution
{
    /**
     * @param list<string> $patrimoine
     */
    public function __construct(
        public string $codeInsee,
        public string $name,
        public string $departmentName,
        public string $departmentCode,
        public float $latitude,
        public float $longitude,
        public int $population,
        public array $patrimoine,
        public ?string $wikipediaUrl,
    ) {
    }
}
