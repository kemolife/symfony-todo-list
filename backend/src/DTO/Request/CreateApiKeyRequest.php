<?php

declare(strict_types=1);

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['name', 'permissions'])]
final class CreateApiKeyRequest
{
    #[OA\Property(type: 'string', maxLength: 100, example: 'CI Deploy Key')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name = '';

    #[OA\Property(type: 'string', nullable: true, example: 'Used by GitHub Actions')]
    #[Assert\Length(max: 65535)]
    public ?string $description = '';

    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string', enum: ['read', 'write', 'delete']),
        example: ['read', 'write'],
    )]
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Choice(choices: ['read', 'write', 'delete']),
    ])]
    public array $permissions = [];
}
