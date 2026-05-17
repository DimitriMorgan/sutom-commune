<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DailyPuzzleRepository;
use App\Service\Clock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sitemap.xml dynamique : pages statiques + entrées d'archive ≤ aujourd'hui.
 * Cache 1h.
 */
final class SitemapController
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly Clock $clock,
        private readonly string $siteUrl = 'https://sutom-commune.fr',
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function __invoke(): Response
    {
        $today = $this->clock->today();
        $entries = $this->puzzleRepo->findArchive($today->modify('-365 days'), $today);

        $urls = [
            ['loc' => $this->siteUrl.'/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => $this->siteUrl.'/archive', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => $this->siteUrl.'/comment-jouer', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => $this->siteUrl.'/a-propos', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => $this->siteUrl.'/mentions-legales', 'changefreq' => 'yearly', 'priority' => '0.2'],
        ];
        foreach ($entries as $entry) {
            $urls[] = [
                'loc' => $this->siteUrl.'/archive/'.$entry->getPuzzleDate()->format('Y-m-d'),
                'lastmod' => $entry->getPuzzleDate()->format('Y-m-d'),
                'changefreq' => 'never',
                'priority' => '0.6',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], \ENT_QUOTES | \ENT_XML1).'</loc>'."\n";
            if (isset($u['lastmod'])) {
                $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        $response = new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
