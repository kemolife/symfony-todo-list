<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[StrongPassword]
    public string $password = '';

    #[Assert\Choice(choices: ['admin', 'user'], message: 'Role must be admin or user')]
    public string $role = 'user';
}
