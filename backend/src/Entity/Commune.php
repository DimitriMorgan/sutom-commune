<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Climate;
use App\Enum\Difficulty;
use App\Repository\CommuneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CommuneRepository::class)]
#[ORM\Table(name: 'commune')]
#[ORM\Index(name: 'commune_is_playable_idx', columns: ['is_playable'])]
#[ORM\Index(name: 'commune_department_code_idx', columns: ['department_code'])]
class Commune
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 5, unique: true)]
    private string $codeInsee;

    #[ORM\Column(length: 160)]
    private string $name;

    /** Forme sans accents, en minuscules, ponctuation → espaces. Indexée via GIN pg_trgm (cf. migration). */
    #[ORM\Column(length: 160)]
    private string $nameNormalized;

    #[ORM\Column(length: 180, unique: true)]
    private string $nameSlug;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $postalCodes = [];

    #[ORM\Column(length: 3)]
    private string $departmentCode;

    #[ORM\Column(length: 80)]
    private string $departmentName;

    #[ORM\Column(length: 3)]
    private string $regionCode;

    #[ORM\Column(length: 80)]
    private string $regionName;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7)]
    private string $longitude;

    #[ORM\Column]
    private int $population;

    #[ORM\Column(nullable: true)]
    private ?int $altitude = null;

    #[ORM\Column(enumType: Climate::class, length: 32)]
    private Climate $climate;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $patrimoine = [];

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $wikipediaUrl = null;

    #[ORM\Column]
    private bool $isPlayable = false;

    #[ORM\Column(enumType: Difficulty::class, length: 16, nullable: true)]
    private ?Difficulty $difficulty = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCodeInsee(): string
    {
        return $this->codeInsee;
    }

    public function setCodeInsee(string $codeInsee): self
    {
        $this->codeInsee = $codeInsee;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNameNormalized(): string
    {
        return $this->nameNormalized;
    }

    public function setNameNormalized(string $nameNormalized): self
    {
        $this->nameNormalized = $nameNormalized;

        return $this;
    }

    public function getNameSlug(): string
    {
        return $this->nameSlug;
    }

    public function setNameSlug(string $nameSlug): self
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    /** @return list<string> */
    public function getPostalCodes(): array
    {
        return $this->postalCodes;
    }

    /** @param list<string> $postalCodes */
    public function setPostalCodes(array $postalCodes): self
    {
        $this->postalCodes = $postalCodes;

        return $this;
    }

    public function getDepartmentCode(): string
    {
        return $this->departmentCode;
    }

    public function setDepartmentCode(string $departmentCode): self
    {
        $this->departmentCode = $departmentCode;

        return $this;
    }

    public function getDepartmentName(): string
    {
        return $this->departmentName;
    }

    public function setDepartmentName(string $departmentName): self
    {
        $this->departmentName = $departmentName;

        return $this;
    }

    public function getRegionCode(): string
    {
        return $this->regionCode;
    }

    public function setRegionCode(string $regionCode): self
    {
        $this->regionCode = $regionCode;

        return $this;
    }

    public function getRegionName(): string
    {
        return $this->regionName;
    }

    public function setRegionName(string $regionName): self
    {
        $this->regionName = $regionName;

        return $this;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;

        return $this;
    }

    public function getAltitude(): ?int
    {
        return $this->altitude;
    }

    public function setAltitude(?int $altitude): self
    {
        $this->altitude = $altitude;

        return $this;
    }

    public function getClimate(): Climate
    {
        return $this->climate;
    }

    public function setClimate(Climate $climate): self
    {
        $this->climate = $climate;

        return $this;
    }

    /** @return list<string> */
    public function getPatrimoine(): array
    {
        return $this->patrimoine;
    }

    /** @param list<string> $patrimoine */
    public function setPatrimoine(array $patrimoine): self
    {
        $this->patrimoine = $patrimoine;

        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): self
    {
        $this->specialite = $specialite;

        return $this;
    }

    public function getWikipediaUrl(): ?string
    {
        return $this->wikipediaUrl;
    }

    public function setWikipediaUrl(?string $wikipediaUrl): self
    {
        $this->wikipediaUrl = $wikipediaUrl;

        return $this;
    }

    public function isPlayable(): bool
    {
        return $this->isPlayable;
    }

    public function setIsPlayable(bool $isPlayable): self
    {
        $this->isPlayable = $isPlayable;

        return $this;
    }

    public function getDifficulty(): ?Difficulty
    {
        return $this->difficulty;
    }

    public function setDifficulty(?Difficulty $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
