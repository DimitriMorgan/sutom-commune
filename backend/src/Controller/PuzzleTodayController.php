<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PuzzleTodayOutput;
use App\Service\Clock;
use App\Service\DailyPuzzleSelector;
use App\Service\GuessOrchestrator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PuzzleTodayController
{
    public function __construct(
        private readonly DailyPuzzleSelector $selector,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Vue publique de l'énigme du jour, sans coordonnées ni nom de la cible.
     * Le DTO PuzzleTodayOutput sert de whitelist anti-fuite.
     */
    #[Route('/api/puzzle/today', name: 'api_puzzle_today', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $puzzle = $this->selector->selectForToday();
        $target = $puzzle->getCommune();

        $output = new PuzzleTodayOutput(
            puzzleNumber: $puzzle->getPuzzleNumber(),
            puzzleDate: $puzzle->getPuzzleDate()->format('Y-m-d'),
            regionName: $target->getRegionName(),
            departmentName: $target->getDepartmentName(),
            departmentCode: $target->getDepartmentCode(),
            theme: $puzzle->getTheme(),
            maxAttempts: GuessOrchestrator::MAX_ATTEMPTS,
        );

        return new JsonResponse($output);
    }
}
