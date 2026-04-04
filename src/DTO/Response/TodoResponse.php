<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\ToDoList;

final readonly class TodoResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $tag,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(ToDoList $todo): self
    {
        return new self(
            id: $todo->getId(),
            name: $todo->getName(),
            description: $todo->getDescription(),
            tag: $todo->getTag(),
            status: $todo->getStatus()->value,
            createdAt: $todo->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $todo->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
