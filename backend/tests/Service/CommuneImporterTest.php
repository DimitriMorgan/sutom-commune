<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\Climate;
use App\Enum\Difficulty;
use App\Repository\CommuneRepository;
use App\Service\CommuneImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CommuneImporterTest extends KernelTestCase
{
    private const FIXTURE_PATH = __DIR__.'/../data/communes-sample-10.json';

    private EntityManagerInterface $em;
    private CommuneRepository $repository;
    private CommuneImporter $importer;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;

        $repo = $container->get(CommuneRepository::class);
        self::assertInstanceOf(CommuneRepository::class, $repo);
        $this->repository = $repo;

        $importer = $container->get(CommuneImporter::class);
        self::assertInstanceOf(CommuneImporter::class, $importer);
        $this->importer = $importer;

        $this->em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');
    }

    public function testImportsTenCommunesFromFixture(): void
    {
        $payloads = $this->loadFixture();

        $summary = $this->importer->importFromIterable($payloads);

        self::assertSame(10, $summary->processed);
        self::assertSame(10, $summary->created);
        self::assertSame(0, $summary->updated);
        self::assertSame(0, $summary->skipped);

        $total = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM commune');
        self::assertSame(10, $total);
    }

    public function testHonfleurIsEnrichedCorrectly(): void
    {
        $this->importer->importFromIterable($this->loadFixture());

        $honfleur = $this->repository->findOneByCodeInsee('14333');
        self::assertNotNull($honfleur);
        self::assertSame('Honfleur', $honfleur->getName());
        self::assertSame('honfleur', $honfleur->getNameNormalized());
        self::assertSame('honfleur-14333', $honfleur->getNameSlug());
        self::assertSame('14', $honfleur->getDepartmentCode());
        self::assertSame('Calvados', $honfleur->getDepartmentName());
        self::assertSame('28', $honfleur->getRegionCode());
        self::assertSame('Normandie', $honfleur->getRegionName());
        self::assertSame(7060, $honfleur->getPopulation());
        self::assertTrue($honfleur->isPlayable());
        self::assertSame(Difficulty::Expert, $honfleur->getDifficulty());
        self::assertSame(Climate::Oceanique, $honfleur->getClimate());
        self::assertEqualsWithDelta(49.4197, (float) $honfleur->getLatitude(), 0.0001);
        self::assertEqualsWithDelta(0.236, (float) $honfleur->getLongitude(), 0.0001);
    }

    public function testNonPlayableCommunesUnder5kAreFlagged(): void
    {
        $this->importer->importFromIterable($this->loadFixture());

        $neris = $this->repository->findOneByCodeInsee('03195');
        self::assertNotNull($neris);
        self::assertFalse($neris->isPlayable());
        self::assertNull($neris->getDifficulty());

        $ileRousse = $this->repository->findOneByCodeInsee('2B134');
        self::assertNotNull($ileRousse);
        self::assertFalse($ileRousse->isPlayable());
        self::assertSame('Haute-Corse', $ileRousse->getDepartmentName());
    }

    public function testAccentedNameNormalizedForFuzzy(): void
    {
        $this->importer->importFromIterable($this->loadFixture());

        $stEtienne = $this->repository->findOneByCodeInsee('42218');
        self::assertNotNull($stEtienne);
        self::assertSame('saint etienne', $stEtienne->getNameNormalized());
        self::assertSame('saint-etienne-42218', $stEtienne->getNameSlug());

        $aix = $this->repository->findOneByCodeInsee('13001');
        self::assertNotNull($aix);
        self::assertSame('aix en provence', $aix->getNameNormalized());
    }

    public function testSecondImportIsIdempotentAndUpdates(): void
    {
        $payloads = $this->loadFixture();

        $first = $this->importer->importFromIterable($payloads);
        self::assertSame(10, $first->created);

        $second = $this->importer->importFromIterable($payloads);
        self::assertSame(10, $second->processed);
        self::assertSame(0, $second->created);
        self::assertSame(10, $second->updated);
        self::assertSame(0, $second->skipped);

        $total = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM commune');
        self::assertSame(10, $total);
    }

    public function testSkipsMalformedPayloads(): void
    {
        $broken = [
            ['nom' => 'Sans code INSEE', 'centre' => ['coordinates' => [1.0, 2.0]]],
            ['code' => '99999', 'nom' => 'Sans centre'],
            ['code' => '99998', 'nom' => 'Coords incomplètes', 'centre' => ['coordinates' => [1.0]]],
        ];

        $summary = $this->importer->importFromIterable($broken);

        self::assertSame(3, $summary->processed);
        self::assertSame(0, $summary->created);
        self::assertSame(0, $summary->updated);
        self::assertSame(3, $summary->skipped);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadFixture(): array
    {
        $raw = file_get_contents(self::FIXTURE_PATH);
        self::assertNotFalse($raw);
        $decoded = json_decode($raw, true);
        self::assertIsArray($decoded);

        $out = [];
        foreach ($decoded as $item) {
            if (\is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
