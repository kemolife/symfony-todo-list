<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiKeyToken extends AbstractToken
{
    public function __construct(
        UserInterface $user,
        private readonly string $apiKey,
    ) {
        parent::__construct($user->getRoles());
        $this->setUser($user);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

}
