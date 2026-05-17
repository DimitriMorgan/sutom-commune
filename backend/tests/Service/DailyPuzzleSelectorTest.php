<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Commune;
use App\Entity\DailyPuzzle;
use App\Enum\Climate;
use App\Enum\Difficulty;
use App\Repository\CommuneRepository;
use App\Repository\DailyPuzzleRepository;
use App\Service\Clock;
use App\Service\DailyPuzzleSelector;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DailyPuzzleSelectorTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommuneRepository $communeRepo;
    private DailyPuzzleRepository $puzzleRepo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;

        $cRepo = $container->get(CommuneRepository::class);
        self::assertInstanceOf(CommuneRepository::class, $cRepo);
        $this->communeRepo = $cRepo;

        $pRepo = $container->get(DailyPuzzleRepository::class);
        self::assertInstanceOf(DailyPuzzleRepository::class, $pRepo);
        $this->puzzleRepo = $pRepo;

        $this->em->getConnection()->executeStatement('TRUNCATE TABLE guess_event, daily_puzzle, commune CASCADE');
    }

    public function testIsIdempotentWithinSameDay(): void
    {
        $this->seedCommunes();
        $selector = $this->buildSelector(new FrozenClock(new \DateTimeImmutable('2026-05-18 12:00')));

        $first = $selector->selectForToday();
        $second = $selector->selectForToday();

        self::assertTrue($first->getId()->equals($second->getId()));
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM daily_puzzle'));
    }

    /**
     * @return list<array{int, ?Difficulty}>
     */
    public static function dayDifficultyMatrix(): array
    {
        return [
            // (day, expectedDifficulty)
            [1, Difficulty::Easy],   // lundi
            [2, Difficulty::Easy],   // mardi
            [3, Difficulty::Medium], // mercredi
            [4, Difficulty::Medium], // jeudi
            [5, Difficulty::Hard],   // vendredi
            [6, Difficulty::Expert], // samedi
            [7, Difficulty::Medium], // dimanche
        ];
    }

    #[DataProvider('dayDifficultyMatrix')]
    public function testPicksDifficultyMatchingDayOfWeek(int $dayOfWeek, Difficulty $expected): void
    {
        $this->seedCommunes();
        // 4 mai 2026 = lundi (day 1). On dérive la date depuis day-of-week.
        $base = new \DateTimeImmutable('2026-05-04'); // lundi
        $date = $base->modify('+'.($dayOfWeek - 1).' days');

        $selector = $this->buildSelector(new FrozenClock($date));
        $puzzle = $selector->selectForDate($date);

        self::assertSame($expected, $puzzle->getCommune()->getDifficulty());
    }

    public function testExcludesCommunesUsedInLast365Days(): void
    {
        // 1 seule commune Easy en base.
        $only = $this->makeCommune('75056', 'Paris', 2_140_000, Difficulty::Easy, '75', '11');
        $this->em->persist($only);
        $this->em->flush();

        // Pré-existant : Paris utilisée il y a 100 jours.
        $past = new DailyPuzzle();
        $past
            ->setPuzzleDate((new \DateTimeImmutable('2026-05-18'))->modify('-100 days'))
            ->setCommune($only)
            ->setPuzzleNumber(1);
        $this->em->persist($past);
        $this->em->flush();

        // Date courante = lundi → demande de difficulté Easy. Or Paris est exclue.
        // Le selector doit lever (aucune candidate après fallback non plus).
        $selector = $this->buildSelector(new FrozenClock(new \DateTimeImmutable('2026-05-18 12:00'))); // lundi

        $this->expectException(\RuntimeException::class);
        $selector->selectForDate(new \DateTimeImmutable('2026-05-18'));
    }

    public function testFallbacksToAnyDifficultyWhenBucketEmpty(): void
    {
        // Aucune commune Easy, mais des Medium dispo.
        $medium = $this->makeCommune('14118', 'Caen', 105_000, Difficulty::Medium, '14', '28');
        $hard = $this->makeCommune('14333', 'Honfleur', 7_060, Difficulty::Expert, '14', '28');
        $this->em->persist($medium);
        $this->em->persist($hard);
        $this->em->flush();

        // Lundi → demande Easy → bucket vide → fallback toutes les jouables.
        $selector = $this->buildSelector(new FrozenClock(new \DateTimeImmutable('2026-05-18 12:00')));
        $puzzle = $selector->selectForDate(new \DateTimeImmutable('2026-05-18'));

        self::assertNotNull($puzzle->getCommune());
    }

    public function testPuzzleNumbersAreUniqueAndIncrementing(): void
    {
        $this->seedCommunes();
        $selector = $this->buildSelector(new FrozenClock(new \DateTimeImmutable('2026-05-04')));

        $p1 = $selector->selectForDate(new \DateTimeImmutable('2026-05-04'));
        $p2 = $selector->selectForDate(new \DateTimeImmutable('2026-05-05'));
        $p3 = $selector->selectForDate(new \DateTimeImmutable('2026-05-06'));

        self::assertSame(1, $p1->getPuzzleNumber());
        self::assertSame(2, $p2->getPuzzleNumber());
        self::assertSame(3, $p3->getPuzzleNumber());
    }

    private function seedCommunes(): void
    {
        // Au moins 1 commune par difficulté.
        $this->em->persist($this->makeCommune('75056', 'Paris', 2_140_000, Difficulty::Easy, '75', '11'));
        $this->em->persist($this->makeCommune('69123', 'Lyon', 518_635, Difficulty::Easy, '69', '84'));
        $this->em->persist($this->makeCommune('14118', 'Caen', 105_000, Difficulty::Easy, '14', '28'));
        $this->em->persist($this->makeCommune('63113', 'Clermont-Ferrand', 143_000, Difficulty::Easy, '63', '84'));
        $this->em->persist($this->makeCommune('72181', 'Le Mans', 144_500, Difficulty::Easy, '72', '52'));
        $this->em->persist($this->makeCommune('11069', 'Carcassonne', 47_000, Difficulty::Medium, '11', '76'));
        $this->em->persist($this->makeCommune('22050', 'Dinan', 14_500, Difficulty::Hard, '22', '53'));
        $this->em->persist($this->makeCommune('14333', 'Honfleur', 7_060, Difficulty::Expert, '14', '28'));
        $this->em->flush();
    }

    private function makeCommune(string $code, string $name, int $pop, Difficulty $diff, string $dept, string $region): Commune
    {
        $c = new Commune();
        $c->setCodeInsee($code)
          ->setName($name)
          ->setNameNormalized(strtolower($name))
          ->setNameSlug(strtolower($name).'-'.$code)
          ->setPostalCodes([])
          ->setDepartmentCode($dept)
          ->setDepartmentName('Dept '.$dept)
          ->setRegionCode($region)
          ->setRegionName('Region '.$region)
          ->setLatitude('48.0000000')
          ->setLongitude('2.0000000')
          ->setPopulation($pop)
          ->setClimate(Climate::Oceanique)
          ->setPatrimoine([])
          ->setIsPlayable(true)
          ->setDifficulty($diff);

        return $c;
    }

    private function buildSelector(FrozenClock $clock): DailyPuzzleSelector
    {
        return new DailyPuzzleSelector(
            $this->puzzleRepo,
            $this->communeRepo,
            $this->em,
            $clock,
        );
    }
}

final class FrozenClock extends Clock
{
    public function __construct(private readonly \DateTimeImmutable $frozen)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->frozen;
    }

    public function today(): \DateTimeImmutable
    {
        return $this->frozen->setTime(0, 0);
    }
}
