<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\CreateUserRequest;
use App\DTO\Request\UpdateUserRequest;
use App\DTO\Response\UserResponse;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\UserService;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'api_users')]
#[IsGranted(UserRole::Admin->value)]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[Route('/', name: 'api_users_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $source = null !== $search && '' !== $search
            ? $this->userService->searchUsers($search)
            : $this->userService->getUsers();

        $users = array_map(
            static fn (User $user) => UserResponse::fromEntity($user),
            $source,
        );

        return $this->json($users);
    }

    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json(UserResponse::fromEntity($this->userService->getUser($id)));
    }

    #[Route('/', name: 'api_user_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateUserRequest $createUserRequest): JsonResponse
    {
        $user = $this->userService->createByAdmin($createUserRequest);
        $this->userService->changeRole($user, $createUserRequest->role);

        return $this->json(UserResponse::fromEntity($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_user_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UpdateUserRequest $updateUserRequest): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (null !== $updateUserRequest->role && (int) $currentUser->getId() === $id) {
            return $this->json(['error' => 'You cannot change your own role.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->updateProfile($id, $updateUserRequest->email, $updateUserRequest->password);
        $wasAdmin = $user->hasRole(UserRole::Admin);
        $this->userService->changeRole($user, $updateUserRequest->role);
        $wasPromoted = !$wasAdmin && $user->hasRole(UserRole::Admin);

        $totpUri = $wasPromoted ? $this->totpAuthenticator->getQRContent($user) : null;

        return $this->json(UserResponse::fromEntity($user, $totpUri));
    }

    #[Route('/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->userService->deleteUser($id);

        return $this->json(['message' => 'User deleted']);
    }

    #[Route('/{id}/api-key', name: 'api_user_revoke_api_key', methods: ['DELETE'])]
    public function revokeApiKey(int $id): JsonResponse
    {
        $this->userService->revokeUserApiKey($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
