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
 * TEST CRITIQUE : vérifie que GET /api/puzzle/today ne fuit JAMAIS
 * latitude, longitude, nom, code INSEE, population, patrimoine, etc.
 * Au moindre ajout côté serveur, ce test doit casser bruyamment.
 */
final class PuzzleAntiCheatTest extends WebTestCase
{
    private const ALLOWED_KEYS = [
        'puzzleNumber',
        'puzzleDate',
        'regionName',
        'departmentName',
        'departmentCode',
        'theme',
        'maxAttempts',
    ];

    private const FORBIDDEN_KEYS = [
        'latitude', 'longitude', 'lat', 'lng', 'coordinates', 'centre',
        'name', 'nom', 'codeInsee', 'code_insee',
        'population', 'patrimoine', 'specialite', 'climate',
        'wikipediaUrl', 'wikipedia_url', 'nameSlug', 'nameNormalized',
        'commune', 'communeId',
    ];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        $em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');

        $target = $this->makeHonfleur();
        $em->persist($target);

        // On utilise le même Clock que le selector pour garantir l'alignement de la date du jour.
        $clock = $container->get(Clock::class);
        self::assertInstanceOf(Clock::class, $clock);

        $puzzle = new DailyPuzzle();
        $puzzle->setPuzzleDate($clock->today())->setCommune($target)->setPuzzleNumber(1);
        $em->persist($puzzle);
        $em->flush();
    }

    public function testTodayEndpointDoesNotLeakTargetData(): void
    {
        $this->client->request('GET', '/api/puzzle/today');
        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $body = (string) $response->getContent();
        $payload = json_decode($body, true);
        self::assertIsArray($payload);

        // Whitelist : aucune clé non listée ne doit apparaître.
        $extraKeys = array_diff(array_keys($payload), self::ALLOWED_KEYS);
        self::assertSame([], array_values($extraKeys), 'Clés inattendues : '.implode(',', $extraKeys));

        // Blacklist : la sérialisation JSON brute ne doit contenir AUCUN signal de la cible.
        $needles = ['Honfleur', '14333', '49.4197', '0.236', '7060', '"latitude"', '"longitude"', '"population"', '"patrimoine"'];
        foreach ($needles as $needle) {
            self::assertStringNotContainsString($needle, $body, "Le payload contient '$needle' alors qu'il ne devrait pas.");
        }

        // Whitelist explicite : champs autorisés présents avec le bon type/valeur.
        self::assertSame('Normandie', $payload['regionName']);
        self::assertSame('Calvados', $payload['departmentName']);
        self::assertSame('14', $payload['departmentCode']);
        self::assertSame(6, $payload['maxAttempts']);
        self::assertNull($payload['theme']);
        self::assertSame(1, $payload['puzzleNumber']);
    }

    public function testForbiddenKeysAreNeverInResponse(): void
    {
        $this->client->request('GET', '/api/puzzle/today');
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        $this->assertNoForbiddenKeyDeep($payload, '');
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     */
    private function assertNoForbiddenKeyDeep(array $data, string $path): void
    {
        foreach ($data as $key => $value) {
            $current = '' === $path ? (string) $key : $path.'.'.(string) $key;
            if (\is_string($key) && \in_array($key, self::FORBIDDEN_KEYS, true)) {
                self::fail("Clé interdite '$key' trouvée à $current");
            }
            if (\is_array($value)) {
                $this->assertNoForbiddenKeyDeep($value, $current);
            }
        }
    }

    private function makeHonfleur(): Commune
    {
        $c = new Commune();
        $c->setCodeInsee('14333')
          ->setName('Honfleur')
          ->setNameNormalized('honfleur')
          ->setNameSlug('honfleur-14333')
          ->setPostalCodes(['14600'])
          ->setDepartmentCode('14')
          ->setDepartmentName('Calvados')
          ->setRegionCode('28')
          ->setRegionName('Normandie')
          ->setLatitude('49.4197000')
          ->setLongitude('0.2360000')
          ->setPopulation(7060)
          ->setClimate(Climate::Oceanique)
          ->setPatrimoine(['port historique', 'maisons à colombages'])
          ->setSpecialite('tourisme et pêche')
          ->setIsPlayable(true)
          ->setDifficulty(Difficulty::Expert);

        return $c;
    }
}
