<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use App\Enum\Climate;
use App\Enum\Difficulty;
use App\Service\Clock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Sitemaps = index racine + sous-sitemap pages (statiques + archive) + sous-sitemap communes.
 * On vérifie le shape XML et le cache, pas le contenu textuel des routes.
 */
final class SitemapControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        $clock = $container->get(Clock::class);
        self::assertInstanceOf(Clock::class, $clock);

        $em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');

        $commune = new Commune()
            ->setCodeInsee('14333')
            ->setName('Honfleur')
            ->setNameNormalized('honfleur')
            ->setNameSlug('honfleur-14333')
            ->setPostalCodes([])
            ->setDepartmentCode('14')
            ->setDepartmentName('Calvados')
            ->setRegionCode('28')
            ->setRegionName('Normandie')
            ->setLatitude('49.4197000')
            ->setLongitude('0.2360000')
            ->setPopulation(7060)
            ->setClimate(Climate::Oceanique)
            ->setPatrimoine([])
            ->setIsPlayable(true)
            ->setDifficulty(Difficulty::Expert);
        $em->persist($commune);

        $puzzle = new DailyPuzzle()
            ->setPuzzleDate($clock->today()->modify('-2 days'))
            ->setCommune($commune)
            ->setPuzzleNumber(1);
        $em->persist($puzzle);
        $em->flush();
    }

    public function testIndexListsChildSitemaps(): void
    {
        $this->client->request('GET', '/sitemap.xml');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/xml; charset=utf-8', $response->headers->get('Content-Type'));

        $body = (string) $response->getContent();
        $doc = new \DOMDocument();
        self::assertTrue($doc->loadXML($body), 'sitemap.xml doit être un XML bien formé');

        $sitemaps = $doc->getElementsByTagName('sitemap');
        self::assertSame(2, $sitemaps->length, 'index = 2 sous-sitemaps');
        self::assertStringContainsString('<loc>https://sutom-commune.com/sitemap-pages.xml</loc>', $body);
        self::assertStringContainsString('<loc>https://sutom-commune.com/sitemap-communes.xml</loc>', $body);
    }

    public function testPagesSitemapHasStaticAndArchiveUrls(): void
    {
        $this->client->request('GET', '/sitemap-pages.xml');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $body = (string) $response->getContent();
        $doc = new \DOMDocument();
        self::assertTrue($doc->loadXML($body), 'sitemap-pages.xml doit être un XML bien formé');

        $urls = $doc->getElementsByTagName('url');
        self::assertGreaterThanOrEqual(6, $urls->length, '5 statiques + ≥1 archive');

        foreach (['/', '/archive', '/comment-jouer', '/a-propos', '/mentions-legales'] as $path) {
            self::assertStringContainsString('<loc>https://sutom-commune.com'.$path.'</loc>', $body);
        }
        self::assertStringContainsString('https://sutom-commune.com/archive/', $body);
    }

    public function testCommunesSitemapHasPlayableCommuneUrls(): void
    {
        $this->client->request('GET', '/sitemap-communes.xml');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $body = (string) $response->getContent();
        $doc = new \DOMDocument();
        self::assertTrue($doc->loadXML($body), 'sitemap-communes.xml doit être un XML bien formé');

        self::assertStringContainsString('<loc>https://sutom-commune.com/commune/honfleur-14333</loc>', $body);
    }

    public function testAllSitemapsArePubliclyCacheable(): void
    {
        foreach (['/sitemap.xml', '/sitemap-pages.xml', '/sitemap-communes.xml'] as $path) {
            $this->client->request('GET', $path);
            $cache = (string) $this->client->getResponse()->headers->get('Cache-Control');
            self::assertStringContainsString('public', $cache, $path);
            self::assertStringContainsString('max-age=3600', $cache, $path);
        }
    }
}
