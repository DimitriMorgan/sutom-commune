<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Commune;

final readonly class CommuneSearchResult
{
    public function __construct(
        public string $id,
        public string $codeInsee,
        public string $name,
        public string $departmentName,
        public string $departmentCode,
    ) {
    }

    public static function fromEntity(Commune $commune): self
    {
        return new self(
            id: (string) $commune->getId(),
            codeInsee: $commune->getCodeInsee(),
            name: $commune->getName(),
            departmentName: $commune->getDepartmentName(),
            departmentCode: $commune->getDepartmentCode(),
        );
    }
}
