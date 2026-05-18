<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ShareGridGenerator;
use PHPUnit\Framework\TestCase;

final class ShareGridGeneratorTest extends TestCase
{
    public function testWinningGridFormatsCorrectly(): void
    {
        $generator = new ShareGridGenerator('https://sutom-commune.com');

        $grid = $generator->generate(42, [
            ['tier' => 'red',    'bearing' => 'NE', 'isCorrect' => false, 'isRecognized' => true],
            ['tier' => 'orange', 'bearing' => 'N',  'isCorrect' => false, 'isRecognized' => true],
            ['tier' => 'yellow', 'bearing' => 'NW', 'isCorrect' => false, 'isRecognized' => true],
            ['tier' => 'green',  'bearing' => 'N',  'isCorrect' => true,  'isRecognized' => true],
        ], won: true);

        $expected = "Sutom-Commune #42 · 4/6\n"
            ."🟥↗️\n"
            ."🟧⬆️\n"
            ."🟨↖️\n"
            ."🟩✓\n"
            .'https://sutom-commune.com';

        self::assertSame($expected, $grid);
    }

    public function testLostGridUsesX(): void
    {
        $generator = new ShareGridGenerator('https://sutom-commune.com');

        $grid = $generator->generate(7, array_fill(0, 6, [
            'tier' => 'red',
            'bearing' => 'S',
            'isCorrect' => false,
            'isRecognized' => true,
        ]), won: false);

        self::assertStringContainsString('#7 · X/6', $grid);
        self::assertSame(6, substr_count($grid, '🟥⬇️'));
    }

    public function testUnrecognizedAttemptShowsCrossEmoji(): void
    {
        $generator = new ShareGridGenerator('https://sutom-commune.com');

        $grid = $generator->generate(1, [
            ['tier' => 'red', 'bearing' => null, 'isCorrect' => false, 'isRecognized' => false],
            ['tier' => 'green', 'bearing' => null, 'isCorrect' => true, 'isRecognized' => true],
        ], won: true);

        self::assertStringContainsString("❌\n🟩✓", $grid);
    }

    public function testAllEightCardinalArrowsCovered(): void
    {
        $generator = new ShareGridGenerator('https://sutom-commune.com');
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];

        $grid = $generator->generate(99, array_map(static fn (string $d): array => [
            'tier' => 'orange',
            'bearing' => $d,
            'isCorrect' => false,
            'isRecognized' => true,
        ], $directions), won: false);

        foreach (['⬆️', '↗️', '➡️', '↘️', '⬇️', '↙️', '⬅️', '↖️'] as $arrow) {
            self::assertStringContainsString($arrow, $grid, "arrow $arrow manque dans la grille");
        }
    }
}
