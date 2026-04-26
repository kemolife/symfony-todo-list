<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class PaginatedTodoResponse
{
    /**
     * @param TodoResponse[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
        public int $pages,
    ) {
    }
}
