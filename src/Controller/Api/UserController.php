<?php

namespace App\Controller\Api;

use App\DTO\Request\CreateUserRequest;
use App\DTO\Request\UpdateUserRequest;
use App\DTO\Response\UserResponse;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'api_users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route('/', name: 'api_users_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userService->getUsers();

        return $this->json($users);
    }

    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    public function show(int $id): UserResponse
    {
        $user = $this->userService->getUser($id);

        return UserResponse::fromEntity($user);
    }

    #[Route('/', name: 'api_user_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateUserRequest $createUserRequest): JsonResponse
    {
        $user = $this->userService->createByAdmin($createUserRequest);

        return $this->json($user, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_user_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UpdateUserRequest $updateUserRequest): JsonResponse
    {
        $user = $this->userService->updateUser($id, $updateUserRequest);

        return $this->json($user);
    }

    #[Route('/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->userService->deleteUser($id);

        return $this->json(['message' => 'User deleted']);
    }
}
