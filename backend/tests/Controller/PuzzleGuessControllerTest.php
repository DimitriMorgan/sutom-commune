<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use App\Enum\Climate;
use App\Enum\Difficulty;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Flow complet POST /api/puzzle/guess sur 6 essais — couverture des deux issues :
 * victoire avant le 6e et défaite au 6e.
 */
final class PuzzleGuessControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;

        $this->em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');

        // Cibles : Honfleur. Essais : Paris, Marseille, Brest, Lyon, Caen, Honfleur.
        $honfleur = $this->makeCommune('14333', 'Honfleur', 49.4197, 0.2360, 7060, Difficulty::Expert, '14', '28', 'Calvados', 'Normandie');
        $paris = $this->makeCommune('75056', 'Paris', 48.8566, 2.3522, 2_140_000, Difficulty::Easy, '75', '11', 'Paris', 'Île-de-France');
        $marseille = $this->makeCommune('13055', 'Marseille', 43.2965, 5.3698, 873_076, Difficulty::Easy, '13', '93', 'Bouches-du-Rhône', "Provence-Alpes-Côte d'Azur");
        $brest = $this->makeCommune('29019', 'Brest', 48.3904, -4.4861, 139_163, Difficulty::Easy, '29', '53', 'Finistère', 'Bretagne');
        $lyon = $this->makeCommune('69123', 'Lyon', 45.7640, 4.8357, 518_635, Difficulty::Easy, '69', '84', 'Rhône', 'Auvergne-Rhône-Alpes');
        $caen = $this->makeCommune('14118', 'Caen', 49.1829, -0.3707, 105_000, Difficulty::Easy, '14', '28', 'Calvados', 'Normandie');

        foreach ([$honfleur, $paris, $marseille, $brest, $lyon, $caen] as $c) {
            $this->em->persist($c);
        }

        $puzzle = new DailyPuzzle();
        $puzzle->setPuzzleDate(new \DateTimeImmutable('2026-05-18'))->setCommune($honfleur)->setPuzzleNumber(1);
        $this->em->persist($puzzle);
        $this->em->flush();
    }

    public function testCorrectGuessImmediatelyEndsTheGameAndReturnsSolution(): void
    {
        $payload = $this->guess(1, '14333');

        self::assertTrue($payload['isCorrect']);
        self::assertTrue($payload['gameOver']);
        self::assertArrayHasKey('solution', $payload);
        self::assertSame('14333', $payload['solution']['codeInsee']);
        self::assertSame('Honfleur', $payload['solution']['name']);
        self::assertSame('Calvados', $payload['solution']['departmentName']);
        self::assertSame(7060, $payload['solution']['population']);
        self::assertEqualsWithDelta(49.4197, $payload['solution']['latitude'], 0.001);
        self::assertEqualsWithDelta(0.2360, $payload['solution']['longitude'], 0.001);
        self::assertArrayHasKey('guessPath', $payload);
        self::assertCount(1, $payload['guessPath']);
        self::assertArrayHasKey('shareGrid', $payload);
        self::assertStringContainsString('🟩✓', $payload['shareGrid']);
    }

    public function testWrongGuessReturnsFeedbackAndNextHint(): void
    {
        $payload = $this->guess(1, '75056'); // Paris

        self::assertFalse($payload['isCorrect']);
        self::assertFalse($payload['gameOver']);
        self::assertArrayHasKey('distanceKm', $payload);
        self::assertGreaterThan(100, $payload['distanceKm']); // Paris ↔ Honfleur ~166 km
        self::assertContains($payload['bearing'], ['N', 'NW', 'W']);
        self::assertSame('red', $payload['proximityTier']);
        self::assertArrayHasKey('nextHint', $payload);
        self::assertSame('population', $payload['nextHint']['key']);
        self::assertSame(5, $payload['attemptsRemaining']);
        self::assertArrayNotHasKey('solution', $payload);
        self::assertArrayNotHasKey('shareGrid', $payload);
    }

    public function testSixWrongAttemptsTriggersGameOverWithoutWin(): void
    {
        $sequence = ['75056', '13055', '29019', '69123', '14118', '75056'];

        for ($i = 0; $i < 5; ++$i) {
            $payload = $this->guess($i + 1, $sequence[$i]);
            self::assertFalse($payload['gameOver'], "Attempt $i should not end the game");
        }

        $payload = $this->guess(6, $sequence[5]);
        self::assertTrue($payload['gameOver']);
        self::assertFalse($payload['isCorrect']);
        self::assertArrayHasKey('solution', $payload);
        self::assertSame('Honfleur', $payload['solution']['name']);
        self::assertStringContainsString('X/6', $payload['shareGrid']);
    }

    public function testRejectsAttemptIndexInconsistency(): void
    {
        $this->client->request(
            'POST',
            '/api/puzzle/guess',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'puzzleDate' => '2026-05-18',
                'attemptIndex' => 3,
                'guessCodeInsee' => '75056',
                'sessionHash' => str_repeat('a', 64),
            ]),
        );
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function testRejectsUnknownCommune(): void
    {
        $this->client->request(
            'POST',
            '/api/puzzle/guess',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'puzzleDate' => '2026-05-18',
                'attemptIndex' => 1,
                'guessCodeInsee' => '99999',
                'sessionHash' => str_repeat('b', 64),
            ]),
        );
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function testRejectsMalformedBody(): void
    {
        $this->client->request('POST', '/api/puzzle/guess', [], [], ['CONTENT_TYPE' => 'application/json'], 'not-json');
        self::assertSame(400, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/puzzle/guess', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, mixed>
     */
    private function guess(int $attemptIndex, string $codeInsee, ?string $sessionHash = null): array
    {
        $sessionHash ??= str_repeat('c', 64); // hash hex valide

        $this->client->request(
            'POST',
            '/api/puzzle/guess',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'puzzleDate' => '2026-05-18',
                'attemptIndex' => $attemptIndex,
                'guessCodeInsee' => $codeInsee,
                'sessionHash' => $sessionHash,
            ]),
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $decoded = json_decode((string) $response->getContent(), true);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function makeCommune(string $code, string $name, float $lat, float $lon, int $pop, Difficulty $diff, string $deptCode, string $regionCode, string $deptName, string $regionName): Commune
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
          ->setLatitude(number_format($lat, 7, '.', ''))
          ->setLongitude(number_format($lon, 7, '.', ''))
          ->setPopulation($pop)
          ->setClimate(Climate::Oceanique)
          ->setPatrimoine([])
          ->setIsPlayable(true)
          ->setDifficulty($diff);

        return $c;
    }
}
