<?php

namespace App\DTO\Request;

use App\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateAdminRequest
{
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    public string $email = '';

    #[StrongPassword]
    public string $password = '';

    #[StrongPassword]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
    public string $password_confirmation = '';

    #[Assert\NotBlank]
    public string $admin_secret = '';
}
