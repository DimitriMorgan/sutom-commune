<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DailyPuzzleRepository;
use App\Service\GuessOrchestrator;
use App\Service\InvalidGuessException;
use App\Service\SessionHasher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PuzzleGuessController
{
    public function __construct(
        private readonly DailyPuzzleRepository $puzzleRepo,
        private readonly GuessOrchestrator $orchestrator,
        private readonly SessionHasher $hasher,
    ) {
    }

    #[Route('/api/puzzle/guess', name: 'api_puzzle_guess', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var mixed $decoded */
        $decoded = json_decode((string) $request->getContent(), true);
        if (!\is_array($decoded)) {
            return new JsonResponse(['error' => 'Body JSON invalide.'], 400);
        }

        $puzzleDateStr = \is_string($decoded['puzzleDate'] ?? null) ? $decoded['puzzleDate'] : null;
        $attemptIndex = \is_int($decoded['attemptIndex'] ?? null) ? $decoded['attemptIndex'] : null;
        $guessCodeInsee = \is_string($decoded['guessCodeInsee'] ?? null) ? $decoded['guessCodeInsee'] : null;
        $providedHash = \is_string($decoded['sessionHash'] ?? null) ? $decoded['sessionHash'] : null;

        if (null === $puzzleDateStr || null === $attemptIndex || null === $guessCodeInsee) {
            return new JsonResponse(['error' => 'Champs requis : puzzleDate, attemptIndex, guessCodeInsee.'], 400);
        }

        $puzzleDate = \DateTimeImmutable::createFromFormat('Y-m-d', $puzzleDateStr);
        if (false === $puzzleDate) {
            return new JsonResponse(['error' => 'puzzleDate invalide (format attendu YYYY-MM-DD).'], 400);
        }
        $puzzle = $this->puzzleRepo->findOneByDate($puzzleDate->setTime(0, 0));
        if (null === $puzzle) {
            return new JsonResponse(['error' => 'Aucune énigme pour cette date.'], 404);
        }

        $sessionHash = $this->hasher->hashForRequest($request, $providedHash);

        try {
            $output = $this->orchestrator->submit($puzzle, $sessionHash, $attemptIndex, $guessCodeInsee);
        } catch (InvalidGuessException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }

        $body = json_encode($output, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        // Filtre les nulls pour matcher exactement la forme spec (champs optionnels non envoyés).
        $arr = json_decode($body, true);
        \assert(\is_array($arr));
        $arr = $this->stripNulls($arr);

        return new JsonResponse($arr);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function stripNulls(array $data): array
    {
        return array_filter($data, static fn ($v): bool => null !== $v);
    }
}
