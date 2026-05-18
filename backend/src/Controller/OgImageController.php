<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DailyPuzzleRepository;
use App\Service\Clock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OG image SVG dynamique par énigme (1200×630, format social standard).
 *
 * Pour les archives passées : révèle le nom de la commune (la partie est close).
 * Pour l'énigme du jour : montre juste le numéro + le département (anti-cheat).
 *
 * SVG inline = aucun rendu serveur lourd, cacheable agressivement.
 */
final class OgImageController
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly Clock $clock,
    ) {
    }

    #[Route('/api/og/puzzle/{date}.svg', name: 'api_og_puzzle', methods: ['GET'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
    public function __invoke(string $date): Response
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (false === $parsed) {
            return new Response('invalid date', 400);
        }

        $puzzle = $this->puzzleRepo->findOneByDate($parsed->setTime(0, 0));
        if (null === $puzzle) {
            return new Response('puzzle not found', 404);
        }

        $today = $this->clock->today();
        $isPast = $puzzle->getPuzzleDate() < $today;
        $commune = $puzzle->getCommune();

        $title = sprintf('Énigme #%d', $puzzle->getPuzzleNumber());
        $subtitle = $this->formatDateFr($puzzle->getPuzzleDate());

        if ($isPast) {
            $mainText = $this->escape($commune->getName());
            $sideText = sprintf(
                '%s (%s) — %s',
                $this->escape($commune->getDepartmentName()),
                $this->escape($commune->getDepartmentCode()),
                $this->escape($commune->getRegionName()),
            );
        } else {
            $mainText = 'Commune mystère';
            $sideText = sprintf('Région %s', $this->escape($commune->getRegionName()));
        }

        $svg = $this->renderSvg($title, $subtitle, $mainText, $sideText, $isPast);

        $response = new Response($svg, 200, ['Content-Type' => 'image/svg+xml; charset=utf-8']);
        $response->setPublic();
        $response->setMaxAge($isPast ? 86400 * 30 : 3600);
        $response->setEtag(hash('xxh3', $svg));

        return $response;
    }

    private function renderSvg(string $title, string $subtitle, string $mainText, string $sideText, bool $past): string
    {
        // Tronque le nom de commune si trop long pour tenir
        $mainEsc = $mainText;
        $fontSize = match (true) {
            mb_strlen($mainText) > 22 => 70,
            mb_strlen($mainText) > 15 => 88,
            default => 108,
        };

        $stamp = $past ? '✓ Révélée' : 'À deviner';
        $stampColor = $past ? '#4f7a3a' : '#b97a2a';

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
              <defs>
                <pattern id="grain" x="0" y="0" width="14" height="14" patternUnits="userSpaceOnUse">
                  <path d="M0 0 L14 14 M0 14 L14 0" stroke="#b97a2a" stroke-width="0.5" opacity="0.25"/>
                </pattern>
                <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#faf5e8"/>
                  <stop offset="100%" stop-color="#f3e8cf"/>
                </linearGradient>
              </defs>
              <rect width="1200" height="630" fill="url(#bg)"/>
              <rect width="1200" height="630" fill="url(#grain)"/>
              <rect x="50" y="50" width="1100" height="530" fill="none" stroke="#b97a2a" stroke-width="2" opacity="0.4"/>

              <text x="90" y="130" font-family="Inter, sans-serif" font-size="32" fill="#1a3a5c" font-weight="600">Sutom-Commune</text>
              <text x="90" y="170" font-family="Inter, sans-serif" font-size="22" fill="#4f6b3a">{$title} · {$subtitle}</text>

              <text x="600" y="370" text-anchor="middle" font-family="Georgia, serif" font-size="{$fontSize}" font-weight="700" fill="#1a3a5c">{$mainEsc}</text>

              <text x="600" y="450" text-anchor="middle" font-family="Inter, sans-serif" font-size="28" fill="#2a2520">{$sideText}</text>

              <g transform="translate(1050,540)">
                <rect x="-150" y="-30" width="150" height="40" rx="20" fill="{$stampColor}" opacity="0.15" stroke="{$stampColor}" stroke-width="2"/>
                <text x="-75" y="-3" text-anchor="middle" font-family="Inter, sans-serif" font-size="20" font-weight="600" fill="{$stampColor}">{$stamp}</text>
              </g>

              <text x="90" y="560" font-family="Inter, sans-serif" font-size="20" fill="#1a3a5c" opacity="0.7">sutom-commune.com</text>
            </svg>
            SVG;
    }

    private function formatDateFr(\DateTimeImmutable $date): string
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];
        $day = (int) $date->format('j');
        $month = $months[(int) $date->format('n')] ?? '';
        $year = $date->format('Y');

        return sprintf('%d %s %s', $day, $month, $year);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_XML1, 'UTF-8');
    }
}
