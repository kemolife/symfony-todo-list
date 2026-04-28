<?php

declare(strict_types=1);

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['pre_auth_token', 'code'])]
final class TwoFactorCheckRequest
{
    #[OA\Property(type: 'string', example: 'abc123...', description: 'Token returned by login when 2FA is required')]
    #[Assert\NotBlank]
    public string $pre_auth_token = '';

    #[OA\Property(type: 'string', example: '123456', description: '6-digit TOTP code from authenticator app')]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 6, exactMessage: 'TOTP code must be exactly 6 digits')]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'TOTP code must contain only digits')]
    public string $code = '';
}
