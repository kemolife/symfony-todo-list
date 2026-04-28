<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['email', 'password'])]
final class CreateUserRequest
{
    #[OA\Property(type: 'string', format: 'email', example: 'user@example.com')]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[OA\Property(type: 'string', format: 'password', example: 'Secret1!')]
    #[StrongPassword]
    public string $password = '';

    #[OA\Property(type: 'string', enum: ['admin', 'user'], default: 'user', example: 'user')]
    #[Assert\Choice(choices: ['admin', 'user'], message: 'Role must be admin or user')]
    public string $role = 'user';
}
