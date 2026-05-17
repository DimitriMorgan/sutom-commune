<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GuessEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GuessEventRepository::class)]
#[ORM\Table(name: 'guess_event')]
#[ORM\Index(name: 'guess_event_puzzle_session_idx', columns: ['puzzle_id', 'session_hash'])]
class GuessEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: DailyPuzzle::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DailyPuzzle $puzzle;

    #[ORM\Column(length: 64)]
    private string $sessionHash;

    #[ORM\Column]
    private int $attemptIndex;

    #[ORM\ManyToOne(targetEntity: Commune::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Commune $guessCommune = null;

    #[ORM\Column]
    private bool $isCorrect = false;

    #[ORM\Column(nullable: true)]
    private ?int $distanceKm = null;

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

    public function getPuzzle(): DailyPuzzle
    {
        return $this->puzzle;
    }

    public function setPuzzle(DailyPuzzle $puzzle): self
    {
        $this->puzzle = $puzzle;

        return $this;
    }

    public function getSessionHash(): string
    {
        return $this->sessionHash;
    }

    public function setSessionHash(string $sessionHash): self
    {
        $this->sessionHash = $sessionHash;

        return $this;
    }

    public function getAttemptIndex(): int
    {
        return $this->attemptIndex;
    }

    public function setAttemptIndex(int $attemptIndex): self
    {
        $this->attemptIndex = $attemptIndex;

        return $this;
    }

    public function getGuessCommune(): ?Commune
    {
        return $this->guessCommune;
    }

    public function setGuessCommune(?Commune $commune): self
    {
        $this->guessCommune = $commune;

        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;

        return $this;
    }

    public function getDistanceKm(): ?int
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(?int $distanceKm): self
    {
        $this->distanceKm = $distanceKm;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
