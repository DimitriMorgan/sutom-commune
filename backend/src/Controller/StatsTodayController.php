<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DailyPuzzleRepository;
use App\Repository\GuessEventRepository;
use App\Service\Clock;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stats agrégées de l'énigme du jour (cache 60s via Cache-Control).
 * Ne révèle pas la cible : seulement nombre de sessions et distribution.
 */
final class StatsTodayController
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly GuessEventRepository $guessRepo,
        private readonly Clock $clock,
    ) {
    }

    #[Route('/api/stats/today', name: 'api_stats_today', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $puzzle = $this->puzzleRepo->findOneByDate($this->clock->today());
        if (null === $puzzle) {
            return new JsonResponse(['error' => 'Aucune énigme aujourd\'hui.'], 404);
        }

        $sessions = $this->guessRepo->countSessionsForPuzzle($puzzle);

        $response = new JsonResponse([
            'puzzleNumber' => $puzzle->getPuzzleNumber(),
            'sessions' => $sessions,
        ]);
        $response->setPublic();
        $response->setMaxAge(60);

        return $response;
    }
}
