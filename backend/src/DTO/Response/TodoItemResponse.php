<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\TodoItem;

final readonly class TodoItemResponse
{
    public function __construct(
        public int $id,
        public string $title,
        public bool $isCompleted,
        public ?int $position,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(TodoItem $item): self
    {
        return new self(
            id: $item->getId(),
            title: $item->getTitle(),
            isCompleted: $item->isCompleted(),
            position: $item->getPosition(),
            createdAt: $item->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
