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
        private readonly TwoFactorEnrollmentService $enrollmentService,
    ) {
    }

    public function create(RegisterRequest $dto): User
    {
        $this->assertEmailUnique($dto->email);
        $user = $this->buildUser($dto->email, $dto->password);
        $this->userRepository->save($user);

        return $user;
    }

    public function createAdmin(CreateAdminRequest $dto): User
    {
        $this->assertEmailUnique($dto->email);
        $user = $this->buildUser($dto->email, $dto->password, [UserRole::Admin->value]);
        $user->setTopSecret($this->totpAuthenticator->generateSecret());
        $user->setTwoFactorConfirmed(true);
        $this->userRepository->save($user);

        return $user;
    }

    public function createByAdmin(CreateUserRequest $dto): User
    {
        $this->assertEmailUnique($dto->email);
        $user = $this->buildUser($dto->email, $dto->password);
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

    private function assertEmailUnique(string $email): void
    {
        if (null !== $this->userRepository->findOneBy(['email' => $email])) {
            throw new ConflictHttpException('Email already taken');
        }
    }

    private function buildUser(string $email, string $password, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        if ([] !== $roles) {
            $user->setRoles($roles);
        }

        return $user;
    }

    private function promoteToAdmin(User $user): void
    {
        $user->setRoles([UserRole::Admin->value]);
        $this->enrollmentService->initiate($user);
    }

    private function demoteToUser(User $user): void
    {
        $user->setRoles([]);
        $this->enrollmentService->revoke($user);
    }

    public function confirmTwoFactor(User $user): void
    {
        $user->setTwoFactorConfirmed(true);
        $this->userRepository->save($user);
    }

    public function deleteUser(int $id): void
    {
        $user = $this->getUser($id);
        $this->userRepository->remove($user);
    }
}
