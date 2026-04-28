<?php

declare(strict_types=1);

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema]
final class TodoItemRequest
{
    #[OA\Property(type: 'string', nullable: true, maxLength: 255, example: 'Buy groceries', description: 'Required on create')]
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[OA\Property(type: 'boolean', nullable: true, example: false)]
    public ?bool $isCompleted = null;

    #[OA\Property(type: 'integer', nullable: true, minimum: 0, example: 0)]
    #[Assert\PositiveOrZero]
    public ?int $position = null;
}
