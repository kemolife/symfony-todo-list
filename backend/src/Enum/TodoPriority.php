<?php

declare(strict_types=1);

namespace App\Enum;

enum TodoPriority: int
{
    case High   = 1;
    case Medium = 2;
    case Low    = 3;
}
