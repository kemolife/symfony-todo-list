<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Response\ApiKeyResponse;
use App\Entity\ApiKey;
use App\Enum\UserRole;
use App\Service\ApiKeyService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin', name: 'api_admin_api_keys')]
#[IsGranted(UserRole::Admin->value)]
final class AdminApiKeyController extends AbstractController
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
        private readonly UserService $userService,
    ) {
    }

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

    #[Route('/api-keys/{keyId}', name: '_revoke', methods: ['DELETE'])]
    public function revoke(int $keyId): JsonResponse
    {
        $key = $this->apiKeyService->getKeyById($keyId);
        $this->apiKeyService->revokeKey($key);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
