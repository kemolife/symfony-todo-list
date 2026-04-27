<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\AuditLog;

final readonly class AuditLogResponse
{
    /**
     * @param array<array{field: string, from: mixed, to: mixed}>|null $changes
     */
    public function __construct(
        public int $id,
        public string $entityType,
        public int $entityId,
        public ?string $entityName,
        public string $action,
        public ?array $changes,
        public string $actorEmail,
        public string $occurredAt,
    ) {
    }

    public static function fromEntity(AuditLog $log): self
    {
        return new self(
            id: $log->getId(),
            entityType: $log->getEntityType(),
            entityId: $log->getEntityId(),
            entityName: $log->getEntityName(),
            action: $log->getAction()->value,
            changes: $log->getChanges(),
            actorEmail: $log->getActorEmail(),
            occurredAt: $log->getOccurredAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
