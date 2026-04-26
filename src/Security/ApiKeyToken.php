<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\ApiKeyPermission;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiKeyToken extends AbstractToken
{
    public function __construct(
        UserInterface $user,
        private readonly string $apiKey,
        private readonly array $permissions,
    ) {
        parent::__construct($user->getRoles());
        $this->setUser($user);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return ApiKeyPermission[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(ApiKeyPermission $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
