<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class TwoFactorAuthSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        #[Autowire(service: 'lexik_jwt_authentication.handler.authentication_success')]
        private readonly AuthenticationSuccessHandler $jwtSuccessHandler,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if ($user instanceof User && $user->isTotpAuthenticationEnabled()) {
            $preAuthToken = bin2hex(random_bytes(32));

            $item = $this->cache->getItem('2fa_pending_'.$preAuthToken);
            $item->set($user->getUserIdentifier());
            $item->expiresAfter(300);
            $this->cache->save($item);

            return new JsonResponse([
                'two_factor_required' => true,
                'pre_auth_token' => $preAuthToken,
            ]);
        }

        return $this->jwtSuccessHandler->onAuthenticationSuccess($request, $token);
    }
}
