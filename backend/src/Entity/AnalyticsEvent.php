<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnalyticsEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnalyticsEventRepository::class)]
#[ORM\Table(name: 'analytics_event')]
#[ORM\Index(name: 'analytics_event_type_idx', columns: ['type'])]
#[ORM\Index(name: 'analytics_event_created_at_idx', columns: ['created_at'])]
class AnalyticsEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 64)]
    private string $type;

    #[ORM\Column(length: 64)]
    private string $sessionHash;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $payload = [];

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /** @param array<string, mixed> $payload */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
