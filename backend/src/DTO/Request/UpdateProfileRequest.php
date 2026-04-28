<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequest
{
    #[Assert\Length(max: 100)]
    public ?string $name = null;
}
