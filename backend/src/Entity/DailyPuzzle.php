<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DailyPuzzleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DailyPuzzleRepository::class)]
#[ORM\Table(name: 'daily_puzzle')]
class DailyPuzzle
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'date_immutable', unique: true)]
    private \DateTimeImmutable $puzzleDate;

    #[ORM\ManyToOne(targetEntity: Commune::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Commune $commune;

    #[ORM\Column(unique: true)]
    private int $puzzleNumber;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $theme = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPuzzleDate(): \DateTimeImmutable
    {
        return $this->puzzleDate;
    }

    public function setPuzzleDate(\DateTimeImmutable $puzzleDate): self
    {
        $this->puzzleDate = $puzzleDate;

        return $this;
    }

    public function getCommune(): Commune
    {
        return $this->commune;
    }

    public function setCommune(Commune $commune): self
    {
        $this->commune = $commune;

        return $this;
    }

    public function getPuzzleNumber(): int
    {
        return $this->puzzleNumber;
    }

    public function setPuzzleNumber(int $puzzleNumber): self
    {
        $this->puzzleNumber = $puzzleNumber;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
