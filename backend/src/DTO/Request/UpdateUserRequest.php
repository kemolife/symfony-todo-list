<?php

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[Assert\When(
        expression: 'value !== ""',
        constraints: [new StrongPassword()],
    )]
    public string $password = '';

    #[Assert\Choice(choices: ['admin', 'user'], message: 'Role must be admin or user')]
    public ?string $role = null;
}
