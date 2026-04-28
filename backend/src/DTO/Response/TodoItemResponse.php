<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\TodoItem;
use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class TodoItemResponse
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(type: 'string', example: 'Buy groceries')]
        public string $title,
        #[OA\Property(type: 'boolean', example: false)]
        public bool $isCompleted,
        #[OA\Property(type: 'integer', nullable: true, example: 0)]
        public ?int $position,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
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
