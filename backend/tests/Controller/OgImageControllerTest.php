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
 * OG image dynamique : pour les énigmes passées on peut révéler la commune
 * (la partie est close), mais pour l'énigme du jour le SVG doit montrer
 * « Commune mystère » et la région uniquement.
 */
final class OgImageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private \DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;

        $clock = $container->get(Clock::class);
        self::assertInstanceOf(Clock::class, $clock);
        $this->today = $clock->today();

        $this->em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');

        $past = $this->makeCommune('14333', 'Honfleur', '14', 'Calvados', '28', 'Normandie');
        $current = $this->makeCommune('75056', 'Paris', '75', 'Paris', '11', 'Île-de-France');
        $this->em->persist($past);
        $this->em->persist($current);

        $pastPuzzle = new DailyPuzzle()
            ->setPuzzleDate($this->today->modify('-3 days'))
            ->setCommune($past)
            ->setPuzzleNumber(10);
        $todayPuzzle = new DailyPuzzle()
            ->setPuzzleDate($this->today)
            ->setCommune($current)
            ->setPuzzleNumber(11);
        $this->em->persist($pastPuzzle);
        $this->em->persist($todayPuzzle);
        $this->em->flush();
    }

    public function testPastPuzzleRevealsCommuneName(): void
    {
        $date = $this->today->modify('-3 days')->format('Y-m-d');
        $this->client->request('GET', '/api/og/puzzle/'.$date.'.svg');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/svg+xml; charset=utf-8', $response->headers->get('Content-Type'));

        $body = (string) $response->getContent();
        self::assertStringContainsString('Honfleur', $body);
        self::assertStringContainsString('Calvados', $body);
        self::assertStringContainsString('Révélée', $body);
    }

    public function testCurrentPuzzleHidesCommuneName(): void
    {
        $date = $this->today->format('Y-m-d');
        $this->client->request('GET', '/api/og/puzzle/'.$date.'.svg');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $body = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Commune mystère', $body);
        self::assertStringContainsString('Île-de-France', $body);
        self::assertStringNotContainsString('Paris', $body, 'Le nom de la cible du jour ne doit pas fuiter');
        self::assertStringContainsString('À deviner', $body);
    }

    public function testCacheControlIsSetAggressivelyForPast(): void
    {
        $date = $this->today->modify('-3 days')->format('Y-m-d');
        $this->client->request('GET', '/api/og/puzzle/'.$date.'.svg');
        $cache = (string) $this->client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cache);
        self::assertStringContainsString('max-age=2592000', $cache); // 30 jours
    }

    public function testReturns404WhenPuzzleMissing(): void
    {
        $this->client->request('GET', '/api/og/puzzle/1990-01-01.svg');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testRejectsMalformedDate(): void
    {
        // Route ne matche pas → 404.
        $this->client->request('GET', '/api/og/puzzle/foo.svg');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    private function makeCommune(string $code, string $name, string $deptCode, string $deptName, string $regionCode, string $regionName): Commune
    {
        $c = new Commune();
        $c->setCodeInsee($code)
            ->setName($name)
            ->setNameNormalized(strtolower($name))
            ->setNameSlug(strtolower($name).'-'.$code)
            ->setPostalCodes([])
            ->setDepartmentCode($deptCode)
            ->setDepartmentName($deptName)
            ->setRegionCode($regionCode)
            ->setRegionName($regionName)
            ->setLatitude('48.0000000')
            ->setLongitude('2.0000000')
            ->setPopulation(10_000)
            ->setClimate(Climate::Oceanique)
            ->setPatrimoine([])
            ->setIsPlayable(true)
            ->setDifficulty(Difficulty::Medium);

        return $c;
    }
}
