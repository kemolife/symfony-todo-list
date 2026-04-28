<?php

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['email', 'password', 'password_confirmation'])]
final class RegisterRequest
{
    #[OA\Property(type: 'string', format: 'email', example: 'user@example.com')]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[OA\Property(type: 'string', format: 'password', example: 'Secret1!', description: 'Min 8 chars, uppercase, lowercase, digit, special char')]
    #[StrongPassword]
    public string $password = '';

    #[OA\Property(type: 'string', format: 'password', example: 'Secret1!')]
    #[StrongPassword]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
    public string $password_confirmation = '';
}
