<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\HealthOutput;

/** @implements ProviderInterface<HealthOutput> */
final class HealthProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HealthOutput
    {
        return new HealthOutput(
            status: 'ok',
            service: 'Sutom-Commune API',
            timestamp: new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            apiPlatform: 'enabled',
        );
    }
}
