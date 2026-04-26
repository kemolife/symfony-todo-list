<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\CreateAdminRequest;
use App\DTO\Request\CreateUserRequest;
use App\DTO\Request\RegisterRequest;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly EnrollmentMailer $enrollmentMailer,
    ) {
    }

    public function create(RegisterRequest $registerRequest): User
    {
        if (null !== $this->userRepository->findOneBy(['email' => $registerRequest->email])) {
            throw new ConflictHttpException('Email already taken');
        }

        $user = new User();
        $user->setEmail($registerRequest->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $registerRequest->password));

        $this->userRepository->save($user);

        return $user;
    }

    public function createAdmin(CreateAdminRequest $createAdminRequest): User
    {
        if (null !== $this->userRepository->findOneBy(['email' => $createAdminRequest->email])) {
            throw new ConflictHttpException('Email already taken');
        }

        $user = new User();
        $user->setEmail($createAdminRequest->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $createAdminRequest->password));
        $user->setRoles([UserRole::Admin->value]);
        $user->setTopSecret($this->totpAuthenticator->generateSecret());
        $user->setTwoFactorConfirmed(true);

        $this->userRepository->save($user);

        return $user;
    }

    public function createByAdmin(CreateUserRequest $request): User
    {
        if (null !== $this->userRepository->findOneBy(['email' => $request->email])) {
            throw new ConflictHttpException('Email already taken');
        }

        $user = new User();
        $user->setEmail($request->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));

        $this->userRepository->save($user);

        return $user;
    }

    public function getUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function searchUsers(string $search): array
    {
        return $this->userRepository->searchByEmail($search);
    }

    public function getUser(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }

    public function updateProfile(int $id, string $email, string $password): User
    {
        $user = $this->getUser($id);
        $user->setEmail($email);

        if ('' !== $password) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        }

        $this->userRepository->save($user);

        return $user;
    }

    public function changeRole(User $user, ?string $role): void
    {
        if (null === $role) {
            return;
        }

        $wasAdmin = $user->hasRole(UserRole::Admin);
        $becomesAdmin = 'admin' === $role;

        if (!$wasAdmin && $becomesAdmin) {
            $this->promoteToAdmin($user);
        } elseif ($wasAdmin && !$becomesAdmin) {
            $this->demoteToUser($user);
        }
    }

    private function promoteToAdmin(User $user): void
    {
        $user->setRoles([UserRole::Admin->value]);
        $user->setTopSecret($user->getTopSecret() ?? $this->totpAuthenticator->generateSecret());
        $user->setTwoFactorConfirmed(false);

        $token = bin2hex(random_bytes(32));
        $user->setEnrollmentToken($token);
        $user->setEnrollmentTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->userRepository->save($user);
        $this->enrollmentMailer->send($user);
    }

    private function demoteToUser(User $user): void
    {
        $user->setRoles([]);
        $user->setTopSecret(null);
        $user->setTwoFactorConfirmed(false);
        $user->setEnrollmentToken(null);
        $user->setEnrollmentTokenExpiresAt(null);
        $this->userRepository->save($user);
    }

    public function confirmTwoFactor(User $user): void
    {
        $user->setTwoFactorConfirmed(true);
        $this->userRepository->save($user);
    }

    public function confirmEnrollment(User $user): void
    {
        $user->setTwoFactorConfirmed(true);
        $user->setEnrollmentToken(null);
        $user->setEnrollmentTokenExpiresAt(null);
        $this->userRepository->save($user);
    }

    public function deleteUser(int $id): void
    {
        $user = $this->getUser($id);
        $this->userRepository->remove($user);
    }

    public function revokeUserApiKey(int $id): void
    {
        $this->revokeApiKey($this->getUser($id));
    }

    public function generateApiKey(User $user): User
    {       
        $apiKey = bin2hex(random_bytes(18));
        $user->setApiKey($apiKey);
        $this->userRepository->save($user);

        return $user;
    }

    public function revokeApiKey(User $user): User
    {
        $user->setApiKey(null);
        $this->userRepository->save($user);

        return $user;
    }
}
