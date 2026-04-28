<?php

namespace App\Controller\Api;

use App\DTO\Request\CreateAdminRequest;
use App\Service\UserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Admin')]
#[Route('/api/admin', name: 'api_admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        #[Autowire('%env(ADMIN_SECRET)%')] private readonly string $adminRegistrationSecret,
    ) {
    }

    #[OA\Post(
        summary: 'Register first admin account (requires ADMIN_SECRET)',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: CreateAdminRequest::class))),
        responses: [
            new OA\Response(response: 201, description: 'Admin registered with TOTP setup info', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'totp_secret', type: 'string'),
                    new OA\Property(property: 'totp_uri', type: 'string'),
                ]
            )),
            new OA\Response(response: 403, description: 'Invalid admin_secret'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('/register', name: 'api_admin_register', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAdminRequest $createAdminRequest): JsonResponse
    {
        if ($createAdminRequest->admin_secret !== $this->adminRegistrationSecret) {
            throw new AccessDeniedHttpException('Invalid admin secret');
        }

        $user = $this->userService->createAdmin($createAdminRequest);

        return $this->json([
            'message' => 'Admin registered. Set up your authenticator app then log in.',
            'totp_secret' => $user->getTopSecret(),
            'totp_uri' => $this->totpAuthenticator->getQRContent($user),
        ], Response::HTTP_CREATED);
    }
}
