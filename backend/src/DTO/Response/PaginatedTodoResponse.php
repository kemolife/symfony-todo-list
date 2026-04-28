<?php

declare(strict_types=1);

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class PaginatedTodoResponse
{
    /**
     * @param TodoResponse[] $items
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/TodoResponse'))]
        public array $items,
        #[OA\Property(type: 'integer', example: 42)]
        public int $total,
        #[OA\Property(type: 'integer', example: 1)]
        public int $page,
        #[OA\Property(type: 'integer', example: 10)]
        public int $limit,
        #[OA\Property(type: 'integer', example: 5)]
        public int $pages,
    ) {
    }
}
