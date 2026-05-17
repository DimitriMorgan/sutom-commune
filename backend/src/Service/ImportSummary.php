<?php

declare(strict_types=1);

namespace App\Service;

final readonly class ImportSummary
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $skipped,
        public int $processed,
    ) {
    }
}
