<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Commune;
use App\Enum\Climate;
use App\Enum\Difficulty;
use App\Service\HintProvider;
use PHPUnit\Framework\TestCase;

final class HintProviderTest extends TestCase
{
    private function makeCommune(): Commune
    {
        $c = new Commune();
        $c->setCodeInsee('14333')
          ->setName('Honfleur')
          ->setNameNormalized('honfleur')
          ->setNameSlug('honfleur-14333')
          ->setPostalCodes(['14600'])
          ->setDepartmentCode('14')
          ->setDepartmentName('Calvados')
          ->setRegionCode('28')
          ->setRegionName('Normandie')
          ->setLatitude('49.4197000')
          ->setLongitude('0.2360000')
          ->setPopulation(7060)
          ->setAltitude(15)
          ->setClimate(Climate::Oceanique)
          ->setPatrimoine(['port historique', 'maisons à colombages', 'Église Sainte-Catherine'])
          ->setSpecialite('tourisme et pêche')
          ->setWikipediaUrl('https://fr.wikipedia.org/wiki/Honfleur')
          ->setIsPlayable(true)
          ->setDifficulty(Difficulty::Expert);

        return $c;
    }

    public function testHintOrderIsStable(): void
    {
        self::assertSame(
            [
                HintProvider::HINT_POPULATION,
                HintProvider::HINT_CLIMATE,
                HintProvider::HINT_SPECIALITE,
                HintProvider::HINT_PATRIMOINE,
                HintProvider::HINT_FIRST_LETTER,
            ],
            HintProvider::ORDER,
        );
    }

    public function testPopulationHintRoundsNicely(): void
    {
        $provider = new HintProvider();
        $hint = $provider->hintForAttempt($this->makeCommune(), 1);

        self::assertNotNull($hint);
        self::assertSame('population', $hint['key']);
        self::assertSame('Population', $hint['label']);
        // 7060 → 7000 (palier < 10000 arrondi au 500 le plus proche)
        self::assertStringContainsString('7 000 habitants', $hint['value']);
    }

    public function testClimateHintIncludesAltitudeAboveTwoHundredMetres(): void
    {
        $provider = new HintProvider();

        $highCommune = $this->makeCommune();
        $highCommune->setAltitude(850);
        $hint = $provider->hintForAttempt($highCommune, 2);
        self::assertNotNull($hint);
        self::assertStringContainsString('océanique', $hint['value']);
        self::assertStringContainsString('850 m', $hint['value']);

        $lowCommune = $this->makeCommune(); // altitude 15
        $hint = $provider->hintForAttempt($lowCommune, 2);
        self::assertNotNull($hint);
        self::assertStringNotContainsString('altitude', $hint['value']);
    }

    public function testSpecialiteFallbacksWhenEmpty(): void
    {
        $provider = new HintProvider();

        $with = $this->makeCommune();
        $hint = $provider->hintForAttempt($with, 3);
        self::assertNotNull($hint);
        self::assertSame('tourisme et pêche', $hint['value']);

        $without = $this->makeCommune()->setSpecialite(null);
        $hint = $provider->hintForAttempt($without, 3);
        self::assertNotNull($hint);
        self::assertStringContainsString('aucune spécialité', $hint['value']);
    }

    public function testPatrimoineLimitedToTwoItems(): void
    {
        $provider = new HintProvider();
        $hint = $provider->hintForAttempt($this->makeCommune(), 4);

        self::assertNotNull($hint);
        self::assertSame('port historique · maisons à colombages', $hint['value']);
    }

    public function testFirstLetterIsReturned(): void
    {
        $provider = new HintProvider();
        $hint = $provider->hintForAttempt($this->makeCommune(), 5);

        self::assertNotNull($hint);
        self::assertStringContainsString('commence par H', $hint['value']);
    }

    public function testReturnsNullOutsideRange(): void
    {
        $provider = new HintProvider();

        self::assertNull($provider->hintForAttempt($this->makeCommune(), 0));
        self::assertNull($provider->hintForAttempt($this->makeCommune(), 6));
    }
}
