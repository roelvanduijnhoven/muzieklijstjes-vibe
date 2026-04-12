<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_log_occurred_at', columns: ['occurred_at'])]
#[ORM\Index(name: 'idx_audit_log_entity', columns: ['entity_type', 'entity_id', 'occurred_at'])]
class AuditLog
{
    public const ACTION_INSERT = 'insert';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private string $action;

    #[ORM\Column(length: 191)]
    private string $entityType;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $entityId = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $actorIdentifier = null;

    #[ORM\Column(type: Types::JSON)]
    private array $changes = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getActorIdentifier(): ?string
    {
        return $this->actorIdentifier;
    }

    public function setActorIdentifier(?string $actorIdentifier): static
    {
        $this->actorIdentifier = $actorIdentifier;

        return $this;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function setChanges(array $changes): static
    {
        $this->changes = $changes;

        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function getEntityShortName(): string
    {
        $parts = explode('\\', $this->entityType);

        return end($parts) ?: $this->entityType;
    }

    public function getOccurredAtFormatted(): string
    {
        return $this->occurredAt->format('Y-m-d H:i:s');
    }

    public function getChangesPretty(): string
    {
        return json_encode($this->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function getContextPretty(): string
    {
        if ($this->context === null) {
            return '';
        }

        return json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
