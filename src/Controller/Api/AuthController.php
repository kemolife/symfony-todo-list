<?php

namespace App\Controller\Api;

use App\DTO\Request\RegisterRequest;
use App\DTO\Request\TwoFactorCheckRequest;
use App\Repository\UserRepository;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepository $userRepository,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        #[Autowire(service: 'cache.app')] private readonly CacheItemPoolInterface $cache,
    ) {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest): JsonResponse
    {
        $user = $this->userService->create($registerRequest);
        $token = $this->jwtManager->create($user);

        return $this->json(['token' => $token], Response::HTTP_CREATED);
    }

    #[Route('/2fa/check', name: 'api_auth_2fa_check', methods: ['POST'])]
    public function verify2fa(#[MapRequestPayload] TwoFactorCheckRequest $request): JsonResponse
    {
        $cacheKey = '2fa_pending_'.$request->pre_auth_token;
        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired pre-auth token');
        }

        $user = $this->userRepository->findOneBy(['email' => $item->get()]);

        if ($user === null || !$this->totpAuthenticator->checkCode($user, $request->code)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid 2fa code');
        }

        $this->cache->deleteItem($cacheKey);

        return $this->json(['token' => $this->jwtManager->create($user)]);
    }

    /**
     * Handled by the json_login security firewall — this action is never reached.
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('The json_login firewall should handle this route.');
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $this->jwtManager->logout($this->getUser());
        
        return $this->json(['message' => 'Logged out successfully']);
    }
}
