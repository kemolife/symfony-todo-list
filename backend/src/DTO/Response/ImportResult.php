<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class ImportResult
{
    /** @param string[] $errors */
    public function __construct(
        public int $created,
        public int $failed,
        public array $errors,
    ) {
    }
}
