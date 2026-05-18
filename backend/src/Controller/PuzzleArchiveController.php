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
 * /api/puzzle/archive : format léger volontairement anti-spoil — seuls la région et le
 * département sont exposés (jamais le nom ni le code INSEE de la commune), pour que
 * l'utilisateur puisse rejouer une énigme passée sans en connaître la solution.
 * Limité aux 3 dernières énigmes par défaut.
 *
 * /api/puzzle/archive/{date} renvoie le même DTO public que /api/puzzle/today
 * (sans coordonnées) pour permettre le rejouage : la commune doit toujours être devinée.
 */
final class PuzzleArchiveController
{
    private const DEFAULT_LIMIT = 3;
    private const MAX_LIMIT = 60;

    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly Clock $clock,
    ) {
    }

    #[Route('/api/puzzle/archive', name: 'api_puzzle_archive_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $today = $this->clock->today();

        $limit = max(1, min(self::MAX_LIMIT, (int) $request->query->get('limit', (string) self::DEFAULT_LIMIT)));

        // On part d'une fenêtre large pour rester compatible avec d'éventuels appels from/to ;
        // l'écrêtage à $limit garantit qu'on n'expose jamais plus que demandé.
        $fromStr = (string) $request->query->get('from', $today->modify('-365 days')->format('Y-m-d'));
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

        $puzzles = \array_slice($this->puzzleRepo->findArchive($from, $to), 0, $limit);

        $entries = array_map(function ($p) {
            $c = $p->getCommune();

            // Anti-spoil : on n'expose ni name ni codeInsee dans la liste.
            return [
                'puzzleNumber' => $p->getPuzzleNumber(),
                'puzzleDate' => $p->getPuzzleDate()->format('Y-m-d'),
                'regionName' => $c->getRegionName(),
                'regionCode' => $c->getRegionCode(),
                'departmentName' => $c->getDepartmentName(),
                'departmentCode' => $c->getDepartmentCode(),
            ];
        }, $puzzles);

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
