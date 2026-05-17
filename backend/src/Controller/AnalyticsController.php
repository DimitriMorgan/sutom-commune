<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AnalyticsEvent;
use App\Service\SessionHasher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoint d'ingestion d'événements anonymes côté frontend.
 * Accepte un batch d'events {type, payload} et les stocke avec un sessionHash anonymisé.
 *
 * Aucune donnée personnelle. Aucun cookie, aucun pisteur tiers.
 */
final class AnalyticsController
{
    private const ALLOWED_TYPES = [
        'puzzle_started',
        'guess_submitted',
        'puzzle_won',
        'puzzle_lost',
        'share_clicked',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SessionHasher $hasher,
    ) {
    }

    #[Route('/api/analytics/events', name: 'api_analytics_events', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var mixed $decoded */
        $decoded = json_decode((string) $request->getContent(), true);
        if (!\is_array($decoded)) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        $events = $decoded['events'] ?? null;
        if (!\is_array($events)) {
            return new JsonResponse(['error' => 'Champ "events" attendu (array)'], 400);
        }

        $providedHash = \is_string($decoded['sessionHash'] ?? null) ? $decoded['sessionHash'] : null;
        $sessionHash = $this->hasher->hashForRequest($request, $providedHash);

        $accepted = 0;
        foreach ($events as $rawEvent) {
            if (!\is_array($rawEvent)) {
                continue;
            }
            $type = $rawEvent['type'] ?? null;
            if (!\is_string($type) || !\in_array($type, self::ALLOWED_TYPES, true)) {
                continue;
            }
            $payload = $rawEvent['payload'] ?? [];
            if (!\is_array($payload)) {
                continue;
            }

            $entity = new AnalyticsEvent();
            $entity
                ->setType($type)
                ->setSessionHash($sessionHash)
                ->setPayload($payload);
            $this->em->persist($entity);
            ++$accepted;
        }

        $this->em->flush();

        return new JsonResponse(['accepted' => $accepted]);
    }
}
