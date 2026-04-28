<?php

namespace App\Controller\Api;

use App\DTO\Request\RegisterRequest;
use App\DTO\Request\TwoFactorCheckRequest;
use App\DTO\Request\TwoFactorConfirmRequest;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\BlockedTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Authentication')]
#[Route('/api/auth', name: 'api_auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly BlockedTokenManagerInterface $blockedTokenManager,
        private readonly UserRepository $userRepository,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        #[Autowire(service: 'cache.app')] private readonly CacheItemPoolInterface $cache,
    ) {
    }

    #[OA\Post(
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: RegisterRequest::class))),
        responses: [
            new OA\Response(response: 201, description: 'Registered, returns JWT', content: new OA\JsonContent(properties: [new OA\Property(property: 'token', type: 'string')])),
            new OA\Response(response: 409, description: 'Email already taken'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $registerRequest): JsonResponse
    {
        $user = $this->userService->create($registerRequest);
        $token = $this->jwtManager->create($user);

        return $this->json(['token' => $token], Response::HTTP_CREATED);
    }

    #[OA\Post(
        summary: 'Login with email and password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Secret1!'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Returns JWT or 2FA pre-auth token', content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(properties: [new OA\Property(property: 'token', type: 'string')]),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'two_factor_required', type: 'boolean', example: true),
                        new OA\Property(property: 'pre_auth_token', type: 'string'),
                    ]),
                ]
            )),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('The json_login firewall should handle this route.');
    }

    #[OA\Post(
        summary: 'Complete 2FA verification and get JWT',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: TwoFactorCheckRequest::class))),
        responses: [
            new OA\Response(response: 200, description: 'Returns JWT', content: new OA\JsonContent(properties: [new OA\Property(property: 'token', type: 'string')])),
            new OA\Response(response: 401, description: 'Invalid or expired pre-auth token / wrong TOTP code'),
        ]
    )]
    #[Route('/2fa/check', name: 'api_auth_2fa_check', methods: ['POST'])]
    public function verify2fa(#[MapRequestPayload] TwoFactorCheckRequest $request): JsonResponse
    {
        $cacheKey = '2fa_pending_'.$request->pre_auth_token;
        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired pre-auth token');
        }

        $user = $this->userRepository->findOneBy(['email' => $item->get()]);

        if (null === $user || !$this->totpAuthenticator->checkCode($user, $request->code)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid 2fa code');
        }

        $this->cache->deleteItem($cacheKey);

        return $this->json(['token' => $this->jwtManager->create($user)]);
    }

    #[OA\Get(
        summary: 'Get 2FA TOTP setup info (admin only)',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'TOTP QR URI and secret', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'totp_uri', type: 'string', example: 'otpauth://totp/...'),
                    new OA\Property(property: 'totp_secret', type: 'string', example: 'BASE32SECRET'),
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 409, description: '2FA already confirmed'),
        ]
    )]
    #[Route('/2fa/setup', name: 'api_auth_2fa_setup', methods: ['GET'])]
    #[IsGranted(UserRole::Admin->value)]
    public function setup2fa(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorConfirmed()) {
            throw new ConflictHttpException('2FA is already confirmed');
        }

        if (null === $user->getTopSecret()) {
            throw new BadRequestHttpException('No 2FA secret found for this user');
        }

        return $this->json([
            'totp_uri' => $this->totpAuthenticator->getQRContent($user),
            'totp_secret' => $user->getTopSecret(),
        ]);
    }

    #[OA\Post(
        summary: 'Confirm 2FA enrollment with TOTP code (admin only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: TwoFactorConfirmRequest::class))),
        responses: [
            new OA\Response(response: 200, description: '2FA confirmed', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 401, description: 'Invalid TOTP code'),
            new OA\Response(response: 409, description: '2FA already confirmed'),
        ]
    )]
    #[Route('/2fa/confirm', name: 'api_auth_2fa_confirm', methods: ['POST'])]
    #[IsGranted(UserRole::Admin->value)]
    public function confirm2fa(#[MapRequestPayload] TwoFactorConfirmRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorConfirmed()) {
            throw new ConflictHttpException('2FA is already confirmed');
        }

        if (!$this->totpAuthenticator->checkCode($user, $request->code)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid 2FA code');
        }

        $this->userService->confirmTwoFactor($user);

        return $this->json(['message' => '2FA confirmed successfully']);
    }

    #[OA\Post(
        summary: 'Logout and invalidate JWT',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
        ]
    )]
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $rawToken = str_replace('Bearer ', '', $request->headers->get('Authorization', ''));

        if ('' !== $rawToken) {
            $payload = $this->jwtManager->parse($rawToken);
            $this->blockedTokenManager->add($payload);
        }

        return $this->json(['message' => 'Logged out successfully']);
    }
}
