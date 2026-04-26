<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
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

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private ?ApiKey $resolvedApiKey = null;

    public function __construct(private readonly ApiKeyRepository $apiKeyRepository)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Api-Key');
    }

    public function authenticate(Request $request): Passport
    {
        $keyValue = $request->headers->get('X-Api-Key');

        $apiKey = $this->apiKeyRepository->findOneByKeyValue($keyValue);
        if (null === $apiKey) {
            throw new CustomUserMessageAuthenticationException('Invalid API key.');
        }

        $apiKey->setLastUsedAt(new \DateTimeImmutable());
        $this->apiKeyRepository->save($apiKey);
        $this->resolvedApiKey = $apiKey;

        return new SelfValidatingPassport(
            new UserBadge($keyValue, fn () => $apiKey->getUser())
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();

        return new ApiKeyToken($user, $this->resolvedApiKey->getKeyValue(), $this->resolvedApiKey->getPermissions());
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
