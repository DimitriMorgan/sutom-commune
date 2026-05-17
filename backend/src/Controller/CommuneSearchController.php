<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CommuneSearchResult;
use App\Repository\CommuneRepository;
use App\Service\NameNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Autocomplete pour la saisie d'une commune. Délègue à `CommuneRepository::searchByFuzzyName`
 * qui utilise l'extension `pg_trgm` (seuil 0.25, ordre par similarité puis population décroissante).
 *
 * Insensible aux accents, à la casse et à la ponctuation : la requête est passée au
 * `NameNormalizer` avant exécution.
 */
final class CommuneSearchController
{
    private const LIMIT = 8;

    public function __construct(
        private readonly CommuneRepository $repository,
        private readonly NameNormalizer $normalizer,
    ) {
    }

    #[Route('/api/communes/search', name: 'api_communes_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $rawQuery = trim((string) $request->query->get('q', ''));
        $normalized = $this->normalizer->normalize($rawQuery);

        if (mb_strlen($normalized) < 2) {
            return new JsonResponse(['results' => []]);
        }

        $communes = $this->repository->searchByFuzzyName($normalized, self::LIMIT);
        $results = array_map(CommuneSearchResult::fromEntity(...), $communes);

        return new JsonResponse(['results' => $results]);
    }
}
