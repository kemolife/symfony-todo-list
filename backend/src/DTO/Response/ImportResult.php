<?php

declare(strict_types=1);

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class ImportResult
{
    /** @param string[] $errors */
    public function __construct(
        #[OA\Property(type: 'integer', example: 10, description: 'Number of successfully imported rows')]
        public int $created,
        #[OA\Property(type: 'integer', example: 2, description: 'Number of failed rows')]
        public int $failed,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['Row 3: missing name'])]
        public array $errors,
    ) {
    }
}
