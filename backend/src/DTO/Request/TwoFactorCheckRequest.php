<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class TwoFactorCheckRequest
{
    #[Assert\NotBlank]
    public string $pre_auth_token = '';

    #[Assert\NotBlank]
    #[Assert\Length(exactly: 6, exactMessage: 'TOTP code must be exactly 6 digits')]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'TOTP code must contain only digits')]
    public string $code = '';
}
