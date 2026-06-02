<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CommuneRepository;
use App\Repository\DailyPuzzleRepository;
use App\Service\Clock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sitemaps dynamiques. Index racine + deux sous-sitemaps (S7) :
 *   /sitemap.xml          → <sitemapindex> (pointe vers les deux ci-dessous)
 *   /sitemap-pages.xml    → pages statiques + archive ≤ aujourd'hui
 *   /sitemap-communes.xml → une URL par commune jouable (/commune/{slug})
 *
 * Découpé pour rester propre quand /sitemap-communes.xml grossit (~300 URLs
 * aujourd'hui, marge jusqu'à 50k par fichier). Cache 1h.
 */
final class SitemapController
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly CommuneRepository $communeRepo,
        private readonly Clock $clock,
        private readonly string $siteUrl = 'https://sutom-commune.com',
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap_index', methods: ['GET'])]
    public function index(): Response
    {
        $children = [
            $this->siteUrl.'/sitemap-pages.xml',
            $this->siteUrl.'/sitemap-communes.xml',
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($children as $loc) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>'.$this->esc($loc).'</loc>'."\n";
            $xml .= "  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        return $this->xmlResponse($xml);
    }

    #[Route('/sitemap-pages.xml', name: 'sitemap_pages', methods: ['GET'])]
    public function pages(): Response
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
            // changefreq=monthly (et non never) : si on enrichit la page d'une commune
            // archivée plus tard, Google a un signal pour revenir l'indexer.
            $urls[] = [
                'loc' => $this->siteUrl.'/archive/'.$entry->getPuzzleDate()->format('Y-m-d'),
                'lastmod' => $entry->getPuzzleDate()->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $this->xmlResponse($this->renderUrlset($urls));
    }

    #[Route('/sitemap-communes.xml', name: 'sitemap_communes', methods: ['GET'])]
    public function communes(): Response
    {
        $urls = [];
        foreach ($this->communeRepo->findPlayableForSitemap() as $row) {
            $urls[] = [
                'loc' => $this->siteUrl.'/commune/'.$row['nameSlug'],
                'lastmod' => $row['updatedAt']->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        return $this->xmlResponse($this->renderUrlset($urls));
    }

    /**
     * @param list<array{loc: string, lastmod?: string, changefreq: string, priority: string}> $urls
     */
    private function renderUrlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.$this->esc($u['loc']).'</loc>'."\n";
            if (isset($u['lastmod'])) {
                $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    private function xmlResponse(string $xml): Response
    {
        $response = new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_XML1);
    }
}
