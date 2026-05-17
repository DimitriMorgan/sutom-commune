<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\Difficulty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DifficultyTest extends TestCase
{
    /**
     * @return list<array{0: int, 1: ?Difficulty}>
     */
    public static function populationCases(): array
    {
        return [
            [2_140_000, Difficulty::Easy],   // Paris
            [50_000,    Difficulty::Easy],   // borne basse easy
            [49_999,    Difficulty::Medium],
            [15_000,    Difficulty::Medium], // borne basse medium
            [14_999,    Difficulty::Hard],
            [8_000,     Difficulty::Hard],   // borne basse hard
            [7_999,     Difficulty::Expert],
            [5_000,     Difficulty::Expert], // borne basse expert
            [4_999,     null],               // non jouable
            [0,         null],
        ];
    }

    #[DataProvider('populationCases')]
    public function testFromPopulation(int $population, ?Difficulty $expected): void
    {
        self::assertSame($expected, Difficulty::fromPopulation($population));
    }
}
