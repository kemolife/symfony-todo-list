<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmEnrollmentRequest
{
    #[Assert\NotBlank]
    #[Assert\Regex('/^\d{6}$/', message: 'Code must be exactly 6 digits')]
    public string $code = '';
}
