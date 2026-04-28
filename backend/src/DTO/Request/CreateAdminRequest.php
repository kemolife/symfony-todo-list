<?php

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['email', 'password', 'password_confirmation', 'admin_secret'])]
final class CreateAdminRequest
{
    #[OA\Property(type: 'string', format: 'email', example: 'admin@example.com')]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[OA\Property(type: 'string', format: 'password', example: 'Secret1!')]
    #[StrongPassword]
    public string $password = '';

    #[OA\Property(type: 'string', format: 'password', example: 'Secret1!')]
    #[StrongPassword]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
    public string $password_confirmation = '';

    #[OA\Property(type: 'string', example: 'your-admin-secret', description: 'Server-side ADMIN_SECRET env var')]
    #[Assert\NotBlank]
    public string $admin_secret = '';
}
