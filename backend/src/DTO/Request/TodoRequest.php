<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Enum\TodoStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class TodoRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[Assert\Length(max: 100)]
    public ?string $tag = null;

    public ?TodoStatus $status = null;
}
