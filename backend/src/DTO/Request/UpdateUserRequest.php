<?php

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['email'])]
final class UpdateUserRequest
{
    #[OA\Property(type: 'string', format: 'email', example: 'user@example.com')]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[OA\Property(type: 'string', format: 'password', example: 'NewSecret1!', description: 'Leave empty to keep current password')]
    #[Assert\When(
        expression: 'value !== ""',
        constraints: [new StrongPassword()],
    )]
    public string $password = '';

    #[OA\Property(type: 'string', enum: ['admin', 'user'], nullable: true, example: 'user')]
    #[Assert\Choice(choices: ['admin', 'user'], message: 'Role must be admin or user')]
    public ?string $role = null;
}
