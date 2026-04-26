<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\ConfirmEnrollmentRequest;
use App\Repository\UserRepository;
use App\Service\UserService;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/2fa/enroll', name: 'api_2fa_enroll')]
final class EnrollController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly UserService $userService,
    ) {
    }

    #[Route('/{token}', name: '_show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $user = $this->findValidUser($token);

        return $this->json([
            'totp_uri' => $this->totpAuthenticator->getQRContent($user),
            'totp_secret' => $user->getTopSecret(),
        ]);
    }

    #[Route('/{token}', name: '_confirm', methods: ['POST'])]
    public function confirm(string $token, #[MapRequestPayload] ConfirmEnrollmentRequest $request): JsonResponse
    {
        $user = $this->findValidUser($token);

        if (!$this->totpAuthenticator->checkCode($user, $request->code)) {
            return $this->json(['error' => 'Invalid verification code'], Response::HTTP_BAD_REQUEST);
        }

        $this->userService->confirmEnrollment($user);

        return $this->json(['message' => '2FA enrollment confirmed']);
    }

    private function findValidUser(string $token): \App\Entity\User
    {
        $user = $this->userRepository->findOneBy(['enrollmentToken' => $token]);

        if (null === $user || $user->getEnrollmentTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new NotFoundHttpException('Enrollment link not found or expired');
        }

        return $user;
    }
}
