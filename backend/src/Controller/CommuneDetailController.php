<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CommuneDetail;
use App\Repository\CommuneRepository;
use App\Repository\DailyPuzzleRepository;
use App\Service\Clock;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Fiche publique d'une commune jouable : /api/commune/{slug}.
 *
 * Alimente la page longue traîne /commune/{slug} côté front (SEO). Ne renvoie
 * que des faits publics + les apparitions PASSÉES comme énigme. Singulier
 * (`/commune/`) pour ne pas entrer en conflit avec `/api/communes/search`.
 *
 * 404 si le slug est inconnu ou si la commune n'est pas jouable (pas de page
 * indexable pour une commune qui ne sera jamais une énigme).
 */
final class CommuneDetailController
{
    public function __construct(
        private readonly CommuneRepository $communes,
        private readonly DailyPuzzleRepository $puzzles,
        private readonly Clock $clock,
    ) {
    }

    #[Route('/api/commune/{slug}', name: 'api_commune_detail', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function __invoke(string $slug): JsonResponse
    {
        $commune = $this->communes->findOneBySlug($slug);
        if (null === $commune || !$commune->isPlayable()) {
            return new JsonResponse(['error' => 'commune introuvable'], 404);
        }

        $past = $this->puzzles->findPastAppearancesForCommune($commune, $this->clock->today());
        $detail = CommuneDetail::fromEntity($commune, $past);

        $response = new JsonResponse($detail);
        $response->setPublic();
        // 1h : assez frais si on enrichit une fiche, sans marteler la base.
        $response->setMaxAge(3600);

        return $response;
    }
}
