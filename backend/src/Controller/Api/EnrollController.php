<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\ConfirmEnrollmentRequest;
use App\Service\TwoFactorEnrollmentService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: '2FA Enrollment')]
#[Route('/api/auth/2fa/enroll', name: 'api_2fa_enroll')]
final class EnrollController extends AbstractController
{
    public function __construct(
        private readonly TwoFactorEnrollmentService $enrollmentService,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[OA\Get(
        summary: 'Get TOTP QR code for enrollment (token from email)',
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Enrollment token from email link'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'TOTP setup info', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'totp_uri', type: 'string', example: 'otpauth://totp/...'),
                    new OA\Property(property: 'totp_secret', type: 'string', example: 'BASE32SECRET'),
                ]
            )),
            new OA\Response(response: 404, description: 'Token expired or invalid'),
        ]
    )]
    #[Route('/{token}', name: '_show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $user = $this->enrollmentService->findByToken($token);

        return $this->json([
            'totp_uri' => $this->totpAuthenticator->getQRContent($user),
            'totp_secret' => $user->getTopSecret(),
        ]);
    }

    #[OA\Post(
        summary: 'Confirm 2FA enrollment with TOTP code',
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: ConfirmEnrollmentRequest::class))),
        responses: [
            new OA\Response(response: 200, description: 'Enrollment confirmed', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 400, description: 'Invalid TOTP code'),
            new OA\Response(response: 404, description: 'Token expired or invalid'),
        ]
    )]
    #[Route('/{token}', name: '_confirm', methods: ['POST'])]
    public function confirm(string $token, #[MapRequestPayload] ConfirmEnrollmentRequest $request): JsonResponse
    {
        $user = $this->enrollmentService->findByToken($token);
        $this->enrollmentService->confirm($user, $request->code);

        return $this->json(['message' => '2FA enrollment confirmed']);
    }
}
