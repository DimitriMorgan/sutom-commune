<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Dto\HealthOutput;
use App\State\HealthProvider;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/health',
        output: HealthOutput::class,
        provider: HealthProvider::class
    ),
])]
final class HealthResource
{
}
