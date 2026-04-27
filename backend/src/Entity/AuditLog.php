<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AuditLogAction;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** todo_list | todo_item — extensible to any future entity */
    #[ORM\Column(length: 50)]
    private string $entityType;

    #[ORM\Column]
    private int $entityId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entityName;

    #[ORM\Column(enumType: AuditLogAction::class, length: 20)]
    private AuditLogAction $action;

    /** [{field, from, to}, ...] — null for created/deleted */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $changes;

    #[ORM\Column(length: 180)]
    private string $actorEmail;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        string $entityType,
        int $entityId,
        ?string $entityName,
        AuditLogAction $action,
        ?array $changes,
        string $actorEmail,
    ) {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->entityName = $entityName;
        $this->action = $action;
        $this->changes = $changes;
        $this->actorEmail = $actorEmail;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function getAction(): AuditLogAction
    {
        return $this->action;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function getActorEmail(): string
    {
        return $this->actorEmail;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
