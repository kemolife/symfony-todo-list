<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\TodoList;
use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class TodoResponse
{
    /**
     * @param TodoItemResponse[] $items
     */
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
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/TodoItemResponse'))]
        public array $items,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T12:00:00+00:00')]
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
            items: array_map(TodoItemResponse::fromEntity(...), $todo->getTodoItems()->toArray()),
            createdAt: $todo->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $todo->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
