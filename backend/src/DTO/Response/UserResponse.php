<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\User;

final class UserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly array $roles,
        public readonly bool $hasTwoFactor,
        public readonly int $apiKeyCount,
        public readonly ?string $totpSecret = null,
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
