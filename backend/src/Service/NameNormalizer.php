<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class NameNormalizer
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger('fr');
    }

    /**
     * Forme utilisée pour le fuzzy search pg_trgm :
     * minuscules, sans accents, ponctuation remplacée par un espace simple.
     * Exemples :
     *  - "Saint-Étienne" → "saint etienne"
     *  - "L'Île-Rousse"  → "l ile rousse"
     *  - "Aix-en-Provence" → "aix en provence"
     */
    public function normalize(string $name): string
    {
        $ascii = (string) $this->slugger->slug($name, ' ');
        $lowered = strtolower($ascii);

        return trim((string) preg_replace('/\s+/', ' ', $lowered));
    }

    /**
     * Slug URL-safe unique :
     *  - "Saint-Étienne" → "saint-etienne"
     *  - "L'Île-Rousse"  → "l-ile-rousse"
     */
    public function slug(string $name): string
    {
        return strtolower((string) $this->slugger->slug($name, '-'));
    }
}
