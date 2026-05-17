<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Repository\CommuneRepository;
use App\Service\CommuneImporter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Charge un sous-ensemble de communes représentatives pour le dev / la CI.
 * Pour l'import complet (34 945 communes), lancer `bin/console app:communes:import`.
 *
 * Applique ensuite un fichier d'enrichissement (patrimoine, spécialité, wikipediaUrl)
 * pour les communes critiques — voir étape 13 du brief.
 */
final class CommuneFixtures extends Fixture
{
    private const SAMPLE_PATH = __DIR__.'/data/communes-dev-sample.json';
    private const ENRICHMENT_PATH = __DIR__.'/data/communes-enrichment.json';

    public function __construct(
        private readonly CommuneImporter $importer,
        private readonly CommuneRepository $repository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadBaseSample();
        $this->applyEnrichment($manager);
    }

    private function loadBaseSample(): void
    {
        if (!is_readable(self::SAMPLE_PATH)) {
            return;
        }
        $raw = file_get_contents(self::SAMPLE_PATH);
        if (false === $raw) {
            return;
        }
        $decoded = json_decode($raw, true);
        if (!\is_array($decoded)) {
            return;
        }

        $payloads = array_values(array_filter($decoded, 'is_array'));
        $this->importer->importFromIterable($payloads);
    }

    private function applyEnrichment(ObjectManager $manager): void
    {
        if (!is_readable(self::ENRICHMENT_PATH)) {
            return;
        }
        $raw = file_get_contents(self::ENRICHMENT_PATH);
        if (false === $raw) {
            return;
        }
        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);
        if (!\is_array($decoded)) {
            return;
        }

        foreach ($decoded as $codeInsee => $data) {
            if (!\is_array($data)) {
                continue;
            }
            // PHP convertit les keys numeric-string en int au json_decode ; on recast.
            $code = str_pad((string) $codeInsee, 5, '0', STR_PAD_LEFT);
            $commune = $this->repository->findOneByCodeInsee($code);
            if (null === $commune) {
                continue;
            }
            if (\is_string($data['specialite'] ?? null)) {
                $commune->setSpecialite($data['specialite']);
            }
            $patrimoine = $data['patrimoine'] ?? null;
            if (\is_array($patrimoine)) {
                $list = [];
                foreach ($patrimoine as $item) {
                    if (\is_string($item) && '' !== $item) {
                        $list[] = $item;
                    }
                }
                $commune->setPatrimoine($list);
            }
            if (\is_string($data['wikipediaUrl'] ?? null)) {
                $commune->setWikipediaUrl($data['wikipediaUrl']);
            }
            if (\is_int($data['altitude'] ?? null)) {
                $commune->setAltitude($data['altitude']);
            }
            $commune->touch();
        }
        $manager->flush();
    }
}
