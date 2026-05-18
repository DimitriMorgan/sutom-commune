<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use App\Entity\GuessEvent;
use App\Enum\Climate;
use App\Enum\Difficulty;
use App\Service\Clock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Stats agrégées de l'énigme du jour : nombre de sessions uniques.
 * On vérifie qu'on n'expose pas la cible et que le cache est bien posé.
 */
final class StatsTodayControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private \DateTimeImmutable $today;
    private ?DailyPuzzle $todayPuzzle = null;
    private ?Commune $target = null;

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
    }

    public function testReturns404WhenNoPuzzleToday(): void
    {
        $this->client->request('GET', '/api/stats/today');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testReportsZeroSessionsWhenNoGuesses(): void
    {
        $this->seedTodayPuzzle();

        $this->client->request('GET', '/api/stats/today');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(42, $payload['puzzleNumber']);
        self::assertSame(0, $payload['sessions']);
    }

    public function testCountsDistinctSessions(): void
    {
        $this->seedTodayPuzzle();
        $this->seedGuess(str_repeat('a', 64));
        $this->seedGuess(str_repeat('a', 64)); // même session → toujours 1
        $this->seedGuess(str_repeat('b', 64));
        $this->seedGuess(str_repeat('c', 64));
        $this->em->flush();

        $this->client->request('GET', '/api/stats/today');
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(3, $payload['sessions']);
    }

    public function testDoesNotLeakTarget(): void
    {
        $this->seedTodayPuzzle();
        $this->client->request('GET', '/api/stats/today');
        $body = (string) $this->client->getResponse()->getContent();

        foreach (['Honfleur', '14333', 'latitude', 'longitude', 'population', 'patrimoine'] as $needle) {
            self::assertStringNotContainsString($needle, $body, "Stats fuit '$needle'");
        }
    }

    public function testIsPubliclyCacheable(): void
    {
        $this->seedTodayPuzzle();
        $this->client->request('GET', '/api/stats/today');
        $cache = (string) $this->client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cache);
        self::assertStringContainsString('max-age=60', $cache);
    }

    private function seedTodayPuzzle(): void
    {
        $this->target = new Commune()
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
            ->setPatrimoine(['port'])
            ->setIsPlayable(true)
            ->setDifficulty(Difficulty::Expert);
        $this->em->persist($this->target);

        $this->todayPuzzle = new DailyPuzzle()
            ->setPuzzleDate($this->today)
            ->setCommune($this->target)
            ->setPuzzleNumber(42);
        $this->em->persist($this->todayPuzzle);
        $this->em->flush();
    }

    private function seedGuess(string $sessionHash): void
    {
        self::assertNotNull($this->todayPuzzle);
        self::assertNotNull($this->target);

        $event = new GuessEvent()
            ->setPuzzle($this->todayPuzzle)
            ->setSessionHash($sessionHash)
            ->setAttemptIndex(1)
            ->setGuessCommune($this->target)
            ->setIsCorrect(false)
            ->setDistanceKm(50);
        $this->em->persist($event);
    }
}
