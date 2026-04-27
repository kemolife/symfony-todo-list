<?php

declare(strict_types=1);

namespace App\Enum;

enum ApiKeyPermission: string
{
    case Read = 'read';
    case Write = 'write';
    case Delete = 'delete';
}
