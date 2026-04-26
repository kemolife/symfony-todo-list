<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateApiKeyRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name = '';

    #[Assert\Length(max: 65535)]
    public ?string $description = '';

    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Choice(choices: ['read', 'write', 'delete']),
    ])]
    public array $permissions = [];
}
