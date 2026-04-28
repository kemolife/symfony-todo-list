<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\ApiKey;
use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class ApiKeyResponse
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(type: 'string', example: 'CI Deploy Key')]
        public string $name,
        #[OA\Property(type: 'string', nullable: true, example: 'Used by GitHub Actions')]
        public ?string $description,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['read', 'write', 'delete']), example: ['read', 'write'])]
        public array $permissions,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', nullable: true, example: null)]
        public ?string $lastUsedAt,
        #[OA\Property(type: 'string', example: 'sk_live_', description: 'First 8 characters of the key')]
        public string $prefix,
        #[OA\Property(type: 'string', nullable: true, example: 'sk_live_abc123...', description: 'Only returned on creation')]
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
