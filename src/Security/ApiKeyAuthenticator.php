<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use App\Entity\User;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Api-Key');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-Api-Key');

        return new SelfValidatingPassport(
            new UserBadge($apiKey, function (string $key): User {
                $user = $this->userRepository->findOneByApiKey($key);
                if (null === $user) {
                    throw new CustomUserMessageAuthenticationException('Invalid API key.');
                }

                return $user;
            })
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        /** @var \App\Entity\User $user */
        $user = $passport->getUser();

        return new ApiKeyToken($user, $user->getApiKey());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
