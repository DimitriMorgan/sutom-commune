<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\NameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NameNormalizerTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function normalizationCases(): array
    {
        return [
            ['Honfleur', 'honfleur'],
            ['HONFLEUR', 'honfleur'],
            ['Saint-Étienne', 'saint etienne'],
            ['Aix-en-Provence', 'aix en provence'],
            ["L'Île-Rousse", 'l ile rousse'],
            ['Saint-Cyr-sur-Mer', 'saint cyr sur mer'],
            ['Néris-les-Bains', 'neris les bains'],
            ['  Pau ', 'pau'],
        ];
    }

    #[DataProvider('normalizationCases')]
    public function testNormalize(string $input, string $expected): void
    {
        $normalizer = new NameNormalizer();

        self::assertSame($expected, $normalizer->normalize($input));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function slugCases(): array
    {
        return [
            ['Honfleur', 'honfleur'],
            ['Saint-Étienne', 'saint-etienne'],
            ["L'Île-Rousse", 'l-ile-rousse'],
            ['Aix-en-Provence', 'aix-en-provence'],
        ];
    }

    #[DataProvider('slugCases')]
    public function testSlug(string $input, string $expected): void
    {
        $normalizer = new NameNormalizer();

        self::assertSame($expected, $normalizer->slug($input));
    }
}
