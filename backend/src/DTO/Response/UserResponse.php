<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class UserResponse
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public readonly int $id,
        #[OA\Property(type: 'string', format: 'email', example: 'user@example.com')]
        public readonly string $email,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])]
        public readonly array $roles,
        #[OA\Property(type: 'boolean', example: false)]
        public readonly bool $hasTwoFactor,
        #[OA\Property(type: 'integer', example: 2)]
        public readonly int $apiKeyCount,
        #[OA\Property(type: 'string', nullable: true, example: 'BASE32SECRET')]
        public readonly ?string $totpSecret = null,
        #[OA\Property(type: 'string', nullable: true, example: 'otpauth://totp/...')]
        public readonly ?string $totpUri = null,
    ) {
    }

    public static function fromEntity(User $user, ?string $totpUri = null): self
    {
        return new self(
            id: (int) $user->getId(),
            email: $user->getUserIdentifier(),
            roles: $user->getRoles(),
            hasTwoFactor: $user->isTotpAuthenticationEnabled(),
            apiKeyCount: $user->getApiKeys()->count(),
            totpSecret: null !== $totpUri ? $user->getTopSecret() : null,
            totpUri: $totpUri,
        );
    }
}
