<?php

namespace App\Service;

use App\DTO\Request\CreateAdminRequest;
use App\DTO\Request\CreateUserRequest;
use App\DTO\Request\RegisterRequest;
use App\DTO\Request\UpdateUserRequest;
use App\Entity\User;
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
        $user->setRoles(['ROLE_ADMIN']);
        $user->setTopSecret($this->totpAuthenticator->generateSecret());

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

    public function getUser(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }

    public function updateUser(int $id, UpdateUserRequest $updateUserRequest): User
    {
        $user = $this->getUser($id);
        $user->setEmail($updateUserRequest->email);
        if ('' !== $updateUserRequest->password) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $updateUserRequest->password));
        }
        $this->userRepository->save($user);

        return $user;
    }

    public function deleteUser(int $id): void
    {
        $user = $this->getUser($id);
        $this->userRepository->remove($user);
    }
}
