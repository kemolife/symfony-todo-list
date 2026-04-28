<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Response\ApiKeyResponse;
use App\Entity\ApiKey;
use App\Enum\UserRole;
use App\Service\ApiKeyService;
use App\Service\UserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Admin API Keys')]
#[Route('/api/admin', name: 'api_admin_api_keys')]
#[IsGranted(UserRole::Admin->value)]
final class AdminApiKeyController extends AbstractController
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
        private readonly UserService $userService,
    ) {
    }

    #[OA\Get(
        summary: "List all API keys for a specific user",
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: "User's API keys", content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ApiKeyResponse::class)))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    #[Route('/users/{userId}/api-keys', name: '_list_for_user', methods: ['GET'])]
    public function listForUser(int $userId): JsonResponse
    {
        $user = $this->userService->getUser($userId);

        $keys = array_map(
            fn (ApiKey $k) => ApiKeyResponse::fromEntity($k),
            $this->apiKeyService->getKeysForUser($user),
        );

        return $this->json($keys);
    }

    #[OA\Delete(
        summary: 'Revoke any API key (admin)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'keyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Revoked'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'Key not found'),
        ]
    )]
    #[Route('/api-keys/{keyId}', name: '_revoke', methods: ['DELETE'])]
    public function revoke(int $keyId): JsonResponse
    {
        $key = $this->apiKeyService->getKeyById($keyId);
        $this->apiKeyService->revokeKey($key);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
