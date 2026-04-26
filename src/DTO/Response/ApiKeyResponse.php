<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\ApiKey;

final readonly class ApiKeyResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public array $permissions,
        public string $createdAt,
        public ?string $lastUsedAt,
        public string $prefix,
        public ?string $keyValue = null,
    ) {
    }

    public static function fromEntity(ApiKey $key, bool $includeFullKey = false): self
    {
        return new self(
            id: $key->getId(),
            name: $key->getName(),
            description: $key->getDescription(),
            permissions: array_map(fn ($p) => $p->value, $key->getPermissions()),
            createdAt: $key->getCreatedAt()->format(\DateTimeInterface::ATOM),
            lastUsedAt: $key->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
            prefix: substr($key->getKeyValue(), 0, 8),
            keyValue: $includeFullKey ? $key->getKeyValue() : null,
        );
    }
}
