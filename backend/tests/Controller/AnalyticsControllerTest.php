<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AnalyticsEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ingestion d'événements anonymes : doit accepter une liste d'events bien formés,
 * ignorer silencieusement ceux qui ne respectent pas la whitelist, et anonymiser
 * via SessionHasher (jamais d'IP brute en base).
 */
final class AnalyticsControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;

        $this->em->getConnection()->executeStatement('TRUNCATE TABLE analytics_event');
    }

    public function testAcceptsBatchOfWhitelistedEvents(): void
    {
        $this->post([
            'sessionHash' => str_repeat('a', 64),
            'events' => [
                ['type' => 'puzzle_started', 'payload' => ['puzzleNumber' => 42]],
                ['type' => 'guess_submitted', 'payload' => ['attemptIndex' => 1]],
                ['type' => 'puzzle_won', 'payload' => ['attempts' => 4]],
            ],
        ]);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(3, $payload['accepted']);

        $rows = $this->em->getRepository(AnalyticsEvent::class)->findAll();
        self::assertCount(3, $rows);
    }

    public function testFiltersUnknownTypes(): void
    {
        $this->post([
            'sessionHash' => str_repeat('b', 64),
            'events' => [
                ['type' => 'puzzle_started', 'payload' => []],
                ['type' => 'evil_xss', 'payload' => []],
                ['type' => 'puzzle_won', 'payload' => []],
                ['type' => null, 'payload' => []],
                'not-an-array',
            ],
        ]);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(2, $payload['accepted']);
    }

    public function testRejectsMalformedBody(): void
    {
        $this->client->request('POST', '/api/analytics/events', [], [], ['CONTENT_TYPE' => 'application/json'], 'not-json');
        self::assertSame(400, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/analytics/events', [], [], ['CONTENT_TYPE' => 'application/json'], '{"foo":"bar"}');
        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testNeverStoresRawIp(): void
    {
        $this->client->request(
            'POST',
            '/api/analytics/events',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '203.0.113.42'],
            (string) json_encode([
                'sessionHash' => str_repeat('c', 64),
                'events' => [['type' => 'puzzle_started', 'payload' => []]],
            ]),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $rows = $this->em->getRepository(AnalyticsEvent::class)->findAll();
        self::assertCount(1, $rows);
        $stored = $rows[0]->getSessionHash();
        self::assertNotSame('203.0.113.42', $stored);
        self::assertSame(64, \strlen($stored), 'sessionHash doit être un sha256 hex (64 chars)');
    }

    /**
     * @param array<string, mixed> $body
     */
    private function post(array $body): void
    {
        $this->client->request(
            'POST',
            '/api/analytics/events',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($body),
        );
    }
}
