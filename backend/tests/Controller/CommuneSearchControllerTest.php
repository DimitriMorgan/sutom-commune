<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\CommuneImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CommuneSearchControllerTest extends WebTestCase
{
    private const FIXTURE_PATH = __DIR__.'/../data/communes-sample-10.json';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');

        $importer = $container->get(CommuneImporter::class);
        self::assertInstanceOf(CommuneImporter::class, $importer);

        $raw = file_get_contents(self::FIXTURE_PATH);
        self::assertNotFalse($raw);
        $payloads = json_decode($raw, true);
        self::assertIsArray($payloads);
        $importer->importFromIterable($payloads);
    }

    public function testFindsByPrefix(): void
    {
        $names = $this->searchAndExtractNames('honfl');
        self::assertContains('Honfleur', $names);
    }

    public function testFindsWithoutAccents(): void
    {
        $names = $this->searchAndExtractNames('etienne');
        self::assertContains('Saint-Étienne', $names);
    }

    public function testFindsWithMixedCase(): void
    {
        $names = $this->searchAndExtractNames('PARIS');
        self::assertContains('Paris', $names);
    }

    public function testFindsHyphenatedName(): void
    {
        $names = $this->searchAndExtractNames('aix en provence');
        self::assertContains('Aix-en-Provence', $names);
    }

    public function testFindsApostropheName(): void
    {
        $names = $this->searchAndExtractNames('ile rousse');
        self::assertContains("L'Île-Rousse", $names);
    }

    public function testReturnsExpectedShape(): void
    {
        $payload = $this->search('paris');
        self::assertArrayHasKey('results', $payload);
        self::assertNotEmpty($payload['results']);

        $first = $payload['results'][0];
        self::assertIsArray($first);
        self::assertSame(['id', 'codeInsee', 'name', 'departmentName', 'departmentCode'], array_keys($first));
        self::assertSame('Paris', $first['name']);
        self::assertSame('75056', $first['codeInsee']);
        self::assertSame('Paris', $first['departmentName']);
        self::assertSame('75', $first['departmentCode']);
    }

    public function testReturnsEmptyForShortQuery(): void
    {
        $payload = $this->search('p');
        self::assertSame(['results' => []], $payload);
    }

    public function testReturnsEmptyForEmptyQuery(): void
    {
        $payload = $this->search('');
        self::assertSame(['results' => []], $payload);
    }

    public function testReturnsEmptyForUnknownQuery(): void
    {
        $payload = $this->search('zzzzzzzzz');
        self::assertSame([], $payload['results']);
    }

    public function testResultsOrderedByPopulationOnTie(): void
    {
        // "paris" matche "Paris" et un peu "Aix-en-Provence" (faible similarity).
        // L'ordre repose d'abord sur similarity (Paris en tête), puis sur population.
        $names = $this->searchAndExtractNames('paris');
        self::assertSame('Paris', $names[0]);
    }

    public function testLimitedToEightResults(): void
    {
        // Le fixture contient 10 communes — une requête peu sélective pourrait toutes les ramener.
        // On s'assure que le limit côté repository borne à 8.
        $payload = $this->search('e'); // 1 char → renvoie []
        self::assertSame([], $payload['results']);

        // Avec 2+ chars, vérifier que le nombre de résultats ne dépasse jamais 8.
        $payload = $this->search('saint');
        self::assertLessThanOrEqual(8, \count($payload['results']));
    }

    /**
     * @return array{results: list<array<string, string>>}
     */
    private function search(string $q): array
    {
        $this->client->request('GET', '/api/communes/search?q='.rawurlencode($q));

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $content = (string) $response->getContent();
        $decoded = json_decode($content, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('results', $decoded);
        self::assertIsArray($decoded['results']);

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function searchAndExtractNames(string $q): array
    {
        $payload = $this->search($q);

        return array_values(array_map(static fn (array $r): string => (string) $r['name'], $payload['results']));
    }
}
