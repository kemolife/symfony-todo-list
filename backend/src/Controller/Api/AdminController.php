<?php

namespace App\Controller\Api;

use App\DTO\Request\CreateAdminRequest;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        #[Autowire('%env(ADMIN_SECRET)%')] private readonly string $adminRegistrationSecret,
    ) {
    }

    #[Route('/register', name: 'api_admin_register', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAdminRequest $createAdminRequest): JsonResponse
    {
        if ($createAdminRequest->admin_secret !== $this->adminRegistrationSecret) {
            throw new AccessDeniedHttpException('Invalid admin secret');
        }

        $user = $this->userService->createAdmin($createAdminRequest);
        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'totp_secret' => $user->getTopSecret(),
            'totp_uri' => $this->totpAuthenticator->getQRContent($user),
        ], Response::HTTP_CREATED);
    }
}
