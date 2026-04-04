<?php

declare(strict_types=1);

namespace App\Enum;

enum TodoStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
}
