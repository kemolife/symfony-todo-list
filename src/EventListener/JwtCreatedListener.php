<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
final class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        $payload['roles'] = $user->getRoles();
        $payload['twoFactorConfirmed'] = $user instanceof \App\Entity\User && $user->isTwoFactorConfirmed();

        $event->setData($payload);
    }
}
