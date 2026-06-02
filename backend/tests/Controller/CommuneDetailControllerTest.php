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
 * Fiche commune /api/commune/{slug} : faits publics + apparitions PASSÉES seulement.
 * L'invariant clé est anti-spoil : l'énigme du jour ne doit jamais fuiter ici.
 */
final class CommuneDetailControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Clock $clock;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');

        $clock = $container->get(Clock::class);
        self::assertInstanceOf(Clock::class, $clock);
        $this->clock = $clock;

        // Commune jouable, cible d'une énigme passée (J-3) ET de celle du jour (J0).
        $honfleur = $this->makeCommune('14333', 'Honfleur', 'honfleur-14333', true);
        $em->persist($honfleur);

        // Commune non jouable → pas de page indexable.
        $hamlet = $this->makeCommune('14001', 'Petit-Bourg', 'petit-bourg-14001', false);
        $em->persist($hamlet);

        $past = new DailyPuzzle()
            ->setPuzzleDate($clock->today()->modify('-3 days'))
            ->setCommune($honfleur)
            ->setPuzzleNumber(1);
        $em->persist($past);

        $todayPuzzle = new DailyPuzzle()
            ->setPuzzleDate($clock->today())
            ->setCommune($honfleur)
            ->setPuzzleNumber(2);
        $em->persist($todayPuzzle);

        $em->flush();
    }

    private function makeCommune(string $insee, string $name, string $slug, bool $playable): Commune
    {
        return new Commune()
            ->setCodeInsee($insee)
            ->setName($name)
            ->setNameNormalized(mb_strtolower($name))
            ->setNameSlug($slug)
            ->setPostalCodes(['14600'])
            ->setDepartmentCode('14')
            ->setDepartmentName('Calvados')
            ->setRegionCode('28')
            ->setRegionName('Normandie')
            ->setLatitude('49.4197000')
            ->setLongitude('0.2360000')
            ->setPopulation(7060)
            ->setClimate(Climate::Oceanique)
            ->setPatrimoine(['Vieux Bassin'])
            ->setIsPlayable($playable)
            ->setDifficulty($playable ? Difficulty::Expert : null);
    }

    public function testReturnsDetailForPlayableCommune(): void
    {
        $payload = $this->fetch('honfleur-14333');

        self::assertSame('honfleur-14333', $payload['slug']);
        self::assertSame('Honfleur', $payload['name']);
        self::assertSame('Calvados', $payload['departmentName']);
        self::assertSame('Normandie', $payload['regionName']);
        self::assertSame(7060, $payload['population']);
        self::assertSame('océanique', $payload['climate']);
        self::assertContains('Vieux Bassin', $payload['patrimoine']);
    }

    public function testPastAppearancesExcludeTodaysPuzzle(): void
    {
        $payload = $this->fetch('honfleur-14333');

        self::assertIsArray($payload['pastAppearances']);
        // L'énigme passée (J-3) est listée, jamais celle du jour (J0).
        $dates = array_map(static fn (array $a): string => $a['date'], $payload['pastAppearances']);
        self::assertContains($this->clock->today()->modify('-3 days')->format('Y-m-d'), $dates);
        self::assertNotContains($this->clock->today()->format('Y-m-d'), $dates);
        self::assertCount(1, $payload['pastAppearances']);
    }

    public function testUnknownSlugIs404(): void
    {
        $this->client->request('GET', '/api/commune/nexiste-pas-99999');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testNonPlayableCommuneIs404(): void
    {
        $this->client->request('GET', '/api/commune/petit-bourg-14001');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testIsPubliclyCacheable(): void
    {
        $this->client->request('GET', '/api/commune/honfleur-14333');
        $cache = (string) $this->client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cache);
        self::assertStringContainsString('max-age=3600', $cache);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(string $slug): array
    {
        $this->client->request('GET', '/api/commune/'.$slug);
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $decoded = json_decode((string) $response->getContent(), true);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
