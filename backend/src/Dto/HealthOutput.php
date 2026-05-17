<?php

declare(strict_types=1);

namespace App\Dto;

final class HealthOutput
{
    public function __construct(
        public string $status,
        public string $service,
        public string $timestamp,
        public string $apiPlatform,
    ) {
    }
}
