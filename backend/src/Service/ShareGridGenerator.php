<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Construit la grille emoji de partage style Wordle.
 * Format :
 *   Sutom-Commune #42 · 4/6
 *   🟥↗️
 *   🟧⬆️
 *   🟨↖️
 *   🟩✓
 *   https://sutom-commune.com.
 */
final class ShareGridGenerator
{
    private const TIER_EMOJI = [
        ProximityTierResolver::TIER_GREEN => '🟩',
        ProximityTierResolver::TIER_YELLOW => '🟨',
        ProximityTierResolver::TIER_ORANGE => '🟧',
        ProximityTierResolver::TIER_RED => '🟥',
    ];

    private const BEARING_EMOJI = [
        'N' => '⬆️',
        'NE' => '↗️',
        'E' => '➡️',
        'SE' => '↘️',
        'S' => '⬇️',
        'SW' => '↙️',
        'W' => '⬅️',
        'NW' => '↖️',
    ];

    public function __construct(private readonly string $siteUrl = 'https://sutom-commune.com')
    {
    }

    /**
     * @param list<array{tier: string, bearing: ?string, isCorrect: bool, isRecognized: bool}> $attempts
     */
    public function generate(int $puzzleNumber, array $attempts, bool $won, int $maxAttempts = 6): string
    {
        $score = $won ? \count($attempts).'/'.$maxAttempts : 'X/'.$maxAttempts;

        $lines = ['Sutom-Commune #'.$puzzleNumber.' · '.$score];
        foreach ($attempts as $attempt) {
            $lines[] = $this->renderRow($attempt);
        }
        $lines[] = $this->siteUrl;

        return implode("\n", $lines);
    }

    /**
     * @param array{tier: string, bearing: ?string, isCorrect: bool, isRecognized: bool} $attempt
     */
    private function renderRow(array $attempt): string
    {
        if (!$attempt['isRecognized']) {
            return '❌';
        }

        $tile = self::TIER_EMOJI[$attempt['tier']] ?? '🟥';

        if ($attempt['isCorrect']) {
            return $tile.'✓';
        }

        $arrow = null === $attempt['bearing'] ? '·' : (self::BEARING_EMOJI[$attempt['bearing']] ?? '·');

        return $tile.$arrow;
    }
}
