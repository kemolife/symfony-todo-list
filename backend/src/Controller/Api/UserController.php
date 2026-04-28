<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\CreateUserRequest;
use App\DTO\Request\UpdateUserRequest;
use App\DTO\Response\UserResponse;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\UserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Users')]
#[Route('/api/users', name: 'api_users')]
#[IsGranted(UserRole::Admin->value)]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[OA\Get(
        summary: 'List all users',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by email'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of users', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserResponse::class)))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
        ]
    )]
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

    #[OA\Get(
        summary: 'Get a single user',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User details', content: new OA\JsonContent(ref: new Model(type: UserResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json(UserResponse::fromEntity($this->userService->getUser($id)));
    }

    #[OA\Post(
        summary: 'Create a user',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: CreateUserRequest::class))),
        responses: [
            new OA\Response(response: 201, description: 'User created', content: new OA\JsonContent(ref: new Model(type: UserResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 409, description: 'Email already taken'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('/', name: 'api_user_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateUserRequest $createUserRequest): JsonResponse
    {
        $user = $this->userService->createByAdmin($createUserRequest);
        $this->userService->changeRole($user, $createUserRequest->role);

        return $this->json(UserResponse::fromEntity($user), Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: 'Update a user',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: UpdateUserRequest::class))),
        responses: [
            new OA\Response(response: 200, description: 'User updated', content: new OA\JsonContent(ref: new Model(type: UserResponse::class))),
            new OA\Response(response: 400, description: 'Cannot change own role'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        summary: 'Delete a user',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    #[Route('/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->userService->deleteUser($id);

        return $this->json(['message' => 'User deleted']);
    }
}
