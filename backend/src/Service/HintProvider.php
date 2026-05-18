<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commune;

/**
 * Génère l'indice contextuel à révéler après chaque essai raté.
 * Ordre fixe défini dans le brief (1 indice par essai, max 5 indices après les 5 premiers essais) :
 *  - après essai 1 : population
 *  - après essai 2 : climat (+ altitude si > 200 m)
 *  - après essai 3 : spécialité économique
 *  - après essai 4 : patrimoine (1-2 éléments)
 *  - après essai 5 : première lettre.
 *
 * L'indice 0 (région + département) est inclus dans `/api/puzzle/today` à l'ouverture.
 */
final class HintProvider
{
    public const HINT_POPULATION = 'population';
    public const HINT_CLIMATE = 'climate';
    public const HINT_SPECIALITE = 'specialite';
    public const HINT_PATRIMOINE = 'patrimoine';
    public const HINT_FIRST_LETTER = 'first_letter';

    /** Ordre stable, indexé sur (attemptIndex - 1). */
    public const ORDER = [
        self::HINT_POPULATION,
        self::HINT_CLIMATE,
        self::HINT_SPECIALITE,
        self::HINT_PATRIMOINE,
        self::HINT_FIRST_LETTER,
    ];

    /**
     * @return array{key: string, label: string, value: string}|null
     */
    public function hintForAttempt(Commune $target, int $attemptIndex): ?array
    {
        if ($attemptIndex < 1 || $attemptIndex > 5) {
            return null;
        }

        $key = self::ORDER[$attemptIndex - 1];

        return match ($key) {
            self::HINT_POPULATION => $this->populationHint($target),
            self::HINT_CLIMATE => $this->climateHint($target),
            self::HINT_SPECIALITE => $this->specialiteHint($target),
            self::HINT_PATRIMOINE => $this->patrimoineHint($target),
            self::HINT_FIRST_LETTER => $this->firstLetterHint($target),
        };
    }

    /** @return array{key: string, label: string, value: string} */
    private function populationHint(Commune $target): array
    {
        $pop = $target->getPopulation();
        $rounded = $this->roundPopulation($pop);

        return [
            'key' => self::HINT_POPULATION,
            'label' => 'Population',
            'value' => sprintf('environ %s habitants', number_format($rounded, 0, ',', ' ')),
        ];
    }

    /** @return array{key: string, label: string, value: string} */
    private function climateHint(Commune $target): array
    {
        $value = $target->getClimate()->label();
        $altitude = $target->getAltitude();
        if (null !== $altitude && $altitude > 200) {
            $value .= sprintf(', altitude %s m', number_format($altitude, 0, ',', ' '));
        }

        return [
            'key' => self::HINT_CLIMATE,
            'label' => 'Climat',
            'value' => $value,
        ];
    }

    /** @return array{key: string, label: string, value: string} */
    private function specialiteHint(Commune $target): array
    {
        $value = $target->getSpecialite() ?? 'aucune spécialité notable renseignée';

        return [
            'key' => self::HINT_SPECIALITE,
            'label' => 'Spécialité',
            'value' => $value,
        ];
    }

    /** @return array{key: string, label: string, value: string} */
    private function patrimoineHint(Commune $target): array
    {
        $items = $target->getPatrimoine();
        $value = [] === $items
            ? 'patrimoine local non renseigné'
            : implode(' · ', \array_slice($items, 0, 2));

        return [
            'key' => self::HINT_PATRIMOINE,
            'label' => 'Patrimoine',
            'value' => $value,
        ];
    }

    /** @return array{key: string, label: string, value: string} */
    private function firstLetterHint(Commune $target): array
    {
        $first = mb_substr($target->getName(), 0, 1);

        return [
            'key' => self::HINT_FIRST_LETTER,
            'label' => 'Première lettre',
            'value' => sprintf('le nom commence par %s', $first),
        ];
    }

    private function roundPopulation(int $pop): int
    {
        return match (true) {
            $pop >= 100_000 => (int) round($pop / 10_000) * 10_000,
            $pop >= 10_000 => (int) round($pop / 1_000) * 1_000,
            default => (int) round($pop / 500) * 500,
        };
    }
}
