<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TwoFactorEnrollmentService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly EnrollmentMailer $enrollmentMailer,
    ) {
    }

    public function initiate(User $user): void
    {
        $user->setTopSecret($user->getTopSecret() ?? $this->totpAuthenticator->generateSecret());
        $user->setTwoFactorConfirmed(false);
        $user->setEnrollmentToken(bin2hex(random_bytes(32)));
        $user->setEnrollmentTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->userRepository->save($user);
        $this->enrollmentMailer->send($user);
    }

    public function findByToken(string $token): User
    {
        $user = $this->userRepository->findOneBy(['enrollmentToken' => $token]);

        if (null === $user || $user->getEnrollmentTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new NotFoundHttpException('Enrollment link not found or expired');
        }

        return $user;
    }

    public function confirm(User $user, string $code): void
    {
        if (!$this->totpAuthenticator->checkCode($user, $code)) {
            throw new BadRequestHttpException('Invalid verification code');
        }

        $user->setTwoFactorConfirmed(true);
        $user->setEnrollmentToken(null);
        $user->setEnrollmentTokenExpiresAt(null);

        $this->userRepository->save($user);
    }

    public function revoke(User $user): void
    {
        $user->setTopSecret(null);
        $user->setTwoFactorConfirmed(false);
        $user->setEnrollmentToken(null);
        $user->setEnrollmentTokenExpiresAt(null);

        $this->userRepository->save($user);
    }
}
