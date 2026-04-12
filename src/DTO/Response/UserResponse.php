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
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (int) $user->getId(),
            email: $user->getUserIdentifier(),
            roles: $user->getRoles(),
            hasTwoFactor: $user->isTotpAuthenticationEnabled(),
        );
    }
}
