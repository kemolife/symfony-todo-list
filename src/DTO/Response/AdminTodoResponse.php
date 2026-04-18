<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\TodoList;

final readonly class AdminTodoResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $tag,
        public string $status,
        public ?int $ownerId,
        public ?string $ownerEmail,
        public string $createdAt,
        public string $updatedAt,
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
            ownerId: $todo->getOwner()?->getId(),
            ownerEmail: $todo->getOwner()?->getEmail(),
            createdAt: $todo->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $todo->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
