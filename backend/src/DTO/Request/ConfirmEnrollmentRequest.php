<?php

declare(strict_types=1);

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['code'])]
final class ConfirmEnrollmentRequest
{
    #[OA\Property(type: 'string', example: '123456', description: '6-digit TOTP code from authenticator app')]
    #[Assert\NotBlank]
    #[Assert\Regex('/^\d{6}$/', message: 'Code must be exactly 6 digits')]
    public string $code = '';
}
