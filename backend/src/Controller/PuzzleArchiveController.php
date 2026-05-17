<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PuzzleTodayOutput;
use App\Repository\DailyPuzzleRepository;
use App\Service\Clock;
use App\Service\GuessOrchestrator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoints d'archive : liste des énigmes ≤ aujourd'hui + détail d'une énigme passée.
 *
 * /api/puzzle/archive renvoie un format léger (juste commune name + dept). La cible peut
 * être révélée car ces énigmes sont closes.
 *
 * /api/puzzle/archive/{date} renvoie le même DTO public que /api/puzzle/today
 * (sans coordonnées) pour permettre le rejouage : la commune doit toujours être devinée.
 */
final class PuzzleArchiveController
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly Clock $clock,
    ) {
    }

    #[Route('/api/puzzle/archive', name: 'api_puzzle_archive_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $today = $this->clock->today();

        $fromStr = (string) $request->query->get('from', $today->modify('-30 days')->format('Y-m-d'));
        $toStr = (string) $request->query->get('to', $today->format('Y-m-d'));

        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $fromStr);
        $to = \DateTimeImmutable::createFromFormat('Y-m-d', $toStr);
        if (false === $from || false === $to) {
            return new JsonResponse(['error' => 'Dates invalides (format YYYY-MM-DD).'], 400);
        }

        // Restreint au passé (énigmes closes uniquement).
        if ($to > $today) {
            $to = $today;
        }

        $entries = array_map(function ($p) {
            $c = $p->getCommune();

            return [
                'puzzleNumber' => $p->getPuzzleNumber(),
                'puzzleDate' => $p->getPuzzleDate()->format('Y-m-d'),
                'commune' => [
                    'codeInsee' => $c->getCodeInsee(),
                    'name' => $c->getName(),
                    'departmentName' => $c->getDepartmentName(),
                    'departmentCode' => $c->getDepartmentCode(),
                ],
            ];
        }, $this->puzzleRepo->findArchive($from, $to));

        return new JsonResponse(['entries' => $entries]);
    }

    #[Route('/api/puzzle/archive/{date}', name: 'api_puzzle_archive_detail', methods: ['GET'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
    public function detail(string $date): JsonResponse
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (false === $parsed) {
            return new JsonResponse(['error' => 'Date invalide.'], 400);
        }

        $puzzle = $this->puzzleRepo->findOneByDate($parsed->setTime(0, 0));
        if (null === $puzzle) {
            return new JsonResponse(['error' => 'Aucune énigme à cette date.'], 404);
        }

        $commune = $puzzle->getCommune();
        $output = new PuzzleTodayOutput(
            puzzleNumber: $puzzle->getPuzzleNumber(),
            puzzleDate: $puzzle->getPuzzleDate()->format('Y-m-d'),
            regionName: $commune->getRegionName(),
            departmentName: $commune->getDepartmentName(),
            departmentCode: $commune->getDepartmentCode(),
            theme: $puzzle->getTheme(),
            maxAttempts: GuessOrchestrator::MAX_ATTEMPTS,
        );

        return new JsonResponse(['puzzle' => $output]);
    }
}
