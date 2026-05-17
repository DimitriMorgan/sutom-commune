<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Pseudonymisation des sessions joueur pour les analytics anti-cheat.
 * Aucune donnée perso stockée : SHA-256 de (date jour + /24 IPv4 + UA).
 *
 * Le hash change chaque jour, ce qui empêche de profiler un visiteur sur la durée
 * tout en permettant de compter les essais uniques par énigme.
 */
final class SessionHasher
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function hashForRequest(Request $request, ?string $providedHash = null): string
    {
        if (\is_string($providedHash) && 64 === \strlen($providedHash) && ctype_xdigit($providedHash)) {
            return $providedHash;
        }

        $ip = (string) $request->getClientIp();
        $userAgent = (string) $request->headers->get('User-Agent', '');
        $day = $this->clock->today()->format('Y-m-d');

        return hash('sha256', $day.'|'.$this->truncatedIp($ip).'|'.$userAgent);
    }

    private function truncatedIp(string $ip): string
    {
        if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Conserve les 48 premiers bits (3 groupes de 16) → /48.
            $segments = explode(':', $ip);

            return implode(':', \array_slice($segments, 0, 3)).'::';
        }

        return 'unknown';
    }
}
