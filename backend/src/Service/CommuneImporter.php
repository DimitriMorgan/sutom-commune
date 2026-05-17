<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commune;
use App\Enum\Difficulty;
use App\Repository\CommuneRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Insert/update idempotent des communes depuis le format JSON renvoyé
 * par https://geo.api.gouv.fr/communes
 *
 * Chaque payload doit ressembler à :
 *  {
 *    "nom": "Honfleur",
 *    "code": "14333",
 *    "codesPostaux": ["14600"],
 *    "codeDepartement": "14",
 *    "codeRegion": "28",
 *    "population": 7060,
 *    "centre": { "type": "Point", "coordinates": [0.236, 49.4197] }
 *  }
 *
 * Le `centre.coordinates` est en ordre GeoJSON : [longitude, latitude].
 */
final class CommuneImporter
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommuneRepository $repository,
        private readonly NameNormalizer $normalizer,
        private readonly RegionsAndDepartments $atlas,
    ) {
    }

    /**
     * @param iterable<array<string, mixed>> $payloads
     */
    public function importFromIterable(iterable $payloads, ?\Closure $onProgress = null): ImportSummary
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $processed = 0;

        $batch = 0;
        foreach ($payloads as $payload) {
            ++$processed;
            $outcome = $this->upsertOne($payload);
            match ($outcome) {
                'created' => ++$created,
                'updated' => ++$updated,
                'skipped' => ++$skipped,
            };

            if (null !== $onProgress) {
                $onProgress($processed);
            }

            if (++$batch >= 500) {
                $this->em->flush();
                $this->em->clear();
                $batch = 0;
            }
        }

        $this->em->flush();
        $this->em->clear();

        return new ImportSummary($created, $updated, $skipped, $processed);
    }

    /**
     * @param array<string, mixed> $payload
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertOne(array $payload): string
    {
        $codeInsee = $this->str($payload['code'] ?? null);
        $name = $this->str($payload['nom'] ?? null);
        $centre = $payload['centre'] ?? null;

        if (null === $codeInsee || null === $name || !\is_array($centre)) {
            return 'skipped';
        }

        $coordinates = $centre['coordinates'] ?? null;
        if (!\is_array($coordinates) || 2 !== \count($coordinates)) {
            return 'skipped';
        }

        $longitude = $this->floatish($coordinates[0]);
        $latitude = $this->floatish($coordinates[1]);
        if (null === $longitude || null === $latitude) {
            return 'skipped';
        }

        $departmentCode = $this->str($payload['codeDepartement'] ?? null) ?? '';
        $regionCode = $this->str($payload['codeRegion'] ?? null) ?? '';
        $population = \is_numeric($payload['population'] ?? null) ? (int) $payload['population'] : 0;
        $postalCodes = $this->stringList($payload['codesPostaux'] ?? []);

        $isNew = false;
        $commune = $this->repository->findOneByCodeInsee($codeInsee);
        if (null === $commune) {
            $commune = new Commune();
            $isNew = true;
        }

        $commune
            ->setCodeInsee($codeInsee)
            ->setName($name)
            ->setNameNormalized($this->normalizer->normalize($name))
            ->setNameSlug($this->buildUniqueSlug($name, $codeInsee))
            ->setPostalCodes($postalCodes)
            ->setDepartmentCode($departmentCode)
            ->setDepartmentName($this->atlas->departmentName($departmentCode))
            ->setRegionCode($regionCode)
            ->setRegionName($this->atlas->regionName($regionCode))
            ->setLatitude(number_format($latitude, 7, '.', ''))
            ->setLongitude(number_format($longitude, 7, '.', ''))
            ->setPopulation($population)
            ->setClimate($this->atlas->climateForRegion($regionCode))
            ->setIsPlayable($population >= 5_000)
            ->setDifficulty(Difficulty::fromPopulation($population));

        $commune->touch();

        if ($isNew) {
            $this->em->persist($commune);

            return 'created';
        }

        return 'updated';
    }

    /**
     * Pour éviter les collisions homonymes (ex: "Sainte-Marie" existe dans 13 départements),
     * on suffixe par le code département. L'unicité du codeInsee garantit qu'on reste idempotent.
     */
    private function buildUniqueSlug(string $name, string $codeInsee): string
    {
        $base = $this->normalizer->slug($name);
        // 5 derniers caractères du code INSEE suffisent à désambiguïser.
        return $base.'-'.$codeInsee;
    }

    private function str(mixed $value): ?string
    {
        if (\is_string($value) && '' !== $value) {
            return $value;
        }

        return null;
    }

    private function floatish(mixed $value): ?float
    {
        if (\is_int($value) || \is_float($value)) {
            return (float) $value;
        }
        if (\is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $v) {
            if (\is_string($v) && '' !== $v) {
                $out[] = $v;
            }
        }

        return $out;
    }
}
