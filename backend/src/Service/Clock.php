<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Source de temps interchangeable pour faciliter les tests
 * (gel de la date pour vérifier la pondération de difficulté par jour).
 *
 * Le fuseau utilisé partout est Europe/Paris : c'est la fenêtre qui définit
 * "l'énigme du jour" pour l'audience cible.
 */
class Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    public function today(): \DateTimeImmutable
    {
        return $this->now()->setTime(0, 0);
    }
}
