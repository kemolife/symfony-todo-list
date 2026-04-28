<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\TodoList;
use App\Enum\TodoPriority;
use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class AdminTodoResponse
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(type: 'string', example: 'My Todo List')]
        public string $name,
        #[OA\Property(type: 'string', nullable: true, example: 'A description')]
        public ?string $description,
        #[OA\Property(type: 'string', nullable: true, example: 'work')]
        public ?string $tag,
        #[OA\Property(type: 'string', enum: ['pending', 'in_progress', 'done'], example: 'pending')]
        public string $status,
        #[OA\Property(type: 'string', enum: ['high', 'medium', 'low'], example: 'medium')]
        public string $priority,
        #[OA\Property(type: 'string', format: 'date', nullable: true, example: '2026-05-15')]
        public ?string $dueDate,
        #[OA\Property(type: 'integer', nullable: true, example: 5)]
        public ?int $ownerId,
        #[OA\Property(type: 'string', nullable: true, format: 'email', example: 'user@example.com')]
        public ?string $ownerEmail,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T12:00:00+00:00')]
        public string $updatedAt,
        #[OA\Property(type: 'string', format: 'date-time', nullable: true, example: null)]
        public ?string $deletedAt,
    ) {
    }

    public static function fromEntity(TodoList $todo): self
    {
        return new self(
            id: $todo->getId(),
            name: $todo->getName(),
            description: $todo->getDescription(),
            tag: $todo->getTag(),
            status: $todo->getStatus()->value,
            priority: match ($todo->getPriority()) {
                TodoPriority::High   => 'high',
                TodoPriority::Medium => 'medium',
                TodoPriority::Low    => 'low',
            },
            dueDate: $todo->getDueDate()?->format('Y-m-d'),
            ownerId: $todo->getOwner()?->getId(),
            ownerEmail: $todo->getOwner()?->getEmail(),
            createdAt: $todo->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $todo->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            deletedAt: $todo->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
