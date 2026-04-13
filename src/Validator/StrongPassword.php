<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class StrongPassword extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: 8, max: 255, minMessage: 'Password must be at least 8 characters long'),
            new Assert\Regex(
                pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                message: 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character',
            ),
        ];
    }
}
