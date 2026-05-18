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
 * Couvre la liste anti-spoil (région/département uniquement) et le détail
 * (DTO public sans coordonnées, comme /api/puzzle/today).
 */
final class PuzzleArchiveControllerTest extends WebTestCase
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

        // Cinq communes dans cinq régions/dpts différents — pour vérifier l'écrêtage à 3.
        // On évite les communes dont le nom est aussi celui du département (Paris, Var…)
        // pour pouvoir asserter l'anti-spoil sans collision.
        $seeds = [
            ['14333', 'Honfleur', '14', 'Calvados', '28', 'Normandie'],
            ['44109', 'Nantes', '44', 'Loire-Atlantique', '52', 'Pays de la Loire'],
            ['13055', 'Marseille', '13', 'Bouches-du-Rhône', '93', "Provence-Alpes-Côte d'Azur"],
            ['29019', 'Brest', '29', 'Finistère', '53', 'Bretagne'],
            ['69123', 'Lyon', '69', 'Rhône', '84', 'Auvergne-Rhône-Alpes'],
        ];

        foreach ($seeds as $i => $row) {
            [$code, $name, $deptCode, $deptName, $regionCode, $regionName] = $row;
            $c = $this->makeCommune($code, $name, $deptCode, $deptName, $regionCode, $regionName);
            $this->em->persist($c);

            $puzzle = new DailyPuzzle();
            $puzzle
                ->setPuzzleDate($this->today->modify(sprintf('-%d days', $i)))
                ->setCommune($c)
                ->setPuzzleNumber(100 - $i);
            $this->em->persist($puzzle);
        }
        $this->em->flush();
    }

    public function testListLimitsToThreeByDefault(): void
    {
        $this->client->request('GET', '/api/puzzle/archive');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertIsArray($payload['entries']);
        self::assertCount(3, $payload['entries']);
    }

    public function testListEntriesAreAntiSpoil(): void
    {
        $this->client->request('GET', '/api/puzzle/archive');
        $body = (string) $this->client->getResponse()->getContent();
        $payload = json_decode($body, true);
        self::assertIsArray($payload);

        // Aucune entrée ne doit jamais contenir le nom ni le code INSEE d'une commune.
        $forbidden = ['Honfleur', 'Nantes', 'Marseille', 'Brest', 'Lyon', '14333', '44109', '13055', '29019', '69123'];
        foreach ($forbidden as $needle) {
            self::assertStringNotContainsString($needle, $body, "Liste d'archive fuit '$needle'");
        }

        $first = $payload['entries'][0];
        self::assertSame(['puzzleNumber', 'puzzleDate', 'regionName', 'regionCode', 'departmentName', 'departmentCode'], array_keys($first));
    }

    public function testListRespectsLimitParameter(): void
    {
        $this->client->request('GET', '/api/puzzle/archive?limit=5');
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertCount(5, $payload['entries']);

        $this->client->request('GET', '/api/puzzle/archive?limit=999');
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        // Borné à MAX_LIMIT=60 → 5 puzzles seulement seedés.
        self::assertCount(5, $payload['entries']);
    }

    public function testListOrderedDescending(): void
    {
        $this->client->request('GET', '/api/puzzle/archive?limit=5');
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        $numbers = array_map(static fn (array $e): int => (int) $e['puzzleNumber'], $payload['entries']);
        $sorted = $numbers;
        rsort($sorted);
        self::assertSame($sorted, $numbers, 'Les entrées doivent être triées par date DESC');
    }

    public function testListRejectsInvalidDates(): void
    {
        $this->client->request('GET', '/api/puzzle/archive?from=not-a-date');
        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testDetailReturnsPuzzleWithoutCoordinates(): void
    {
        $date = $this->today->modify('-1 days')->format('Y-m-d');
        $this->client->request('GET', '/api/puzzle/archive/'.$date);
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $body = (string) $response->getContent();
        $payload = json_decode($body, true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('puzzle', $payload);

        $puzzle = $payload['puzzle'];
        self::assertIsArray($puzzle);
        self::assertSame(['puzzleNumber', 'puzzleDate', 'regionName', 'departmentName', 'departmentCode', 'theme', 'maxAttempts'], array_keys($puzzle));

        // Anti-cheat : aucun signal de la cible.
        foreach (['Nantes', '44109', 'latitude', 'longitude', 'population'] as $needle) {
            self::assertStringNotContainsString($needle, $body, "Detail fuit '$needle'");
        }
    }

    public function testDetailReturns404WhenUnknown(): void
    {
        $this->client->request('GET', '/api/puzzle/archive/1990-01-01');
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDetailRejectsMalformedDate(): void
    {
        // Date au mauvais format → route ne matche pas → 404.
        $this->client->request('GET', '/api/puzzle/archive/foo');
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
