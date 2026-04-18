<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class TodoItemRequest
{
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?bool $isCompleted = null;

    #[Assert\PositiveOrZero]
    public ?int $position = null;
}
