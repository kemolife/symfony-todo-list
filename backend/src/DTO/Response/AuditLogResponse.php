<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\AuditLog;
use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class AuditLogResponse
{
    /**
     * @param array<array{field: string, from: mixed, to: mixed}>|null $changes
     */
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(type: 'string', example: 'TodoList')]
        public string $entityType,
        #[OA\Property(type: 'integer', example: 42)]
        public int $entityId,
        #[OA\Property(type: 'string', nullable: true, example: 'My Todo List')]
        public ?string $entityName,
        #[OA\Property(type: 'string', example: 'update')]
        public string $action,
        #[OA\Property(type: 'array', nullable: true, items: new OA\Items(type: 'object'))]
        public ?array $changes,
        #[OA\Property(type: 'string', format: 'email', example: 'admin@example.com')]
        public string $actorEmail,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
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
