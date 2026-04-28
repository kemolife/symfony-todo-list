<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Enum\TodoStatus;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['name'])]
final class TodoRequest
{
    #[OA\Property(type: 'string', maxLength: 255, example: 'My Todo List')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[OA\Property(type: 'string', nullable: true, example: 'A detailed description')]
    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[OA\Property(type: 'string', nullable: true, maxLength: 100, example: 'work')]
    #[Assert\Length(max: 100)]
    public ?string $tag = null;

    #[OA\Property(type: 'string', enum: ['pending', 'in_progress', 'done'], nullable: true, example: 'pending')]
    public ?TodoStatus $status = null;
}
