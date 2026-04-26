<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\CreateApiKeyRequest;
use App\DTO\Response\ApiKeyResponse;
use App\Entity\ApiKey;
use App\Entity\User;
use App\Enum\ApiKeyPermission;
use App\Enum\UserRole;
use App\Service\ApiKeyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/profile', name: 'api_profile')]
#[IsGranted(UserRole::User->value)]
final class ProfileController extends AbstractController
{
    public function __construct(private readonly ApiKeyService $apiKeyService)
    {
    }

    #[Route('', name: '_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['apiKeyCount' => $user->getApiKeys()->count()]);
    }

    #[Route('/api-keys', name: '_list_api_keys', methods: ['GET'])]
    public function listApiKeys(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $keys = array_map(
            fn (ApiKey $k) => ApiKeyResponse::fromEntity($k),
            $this->apiKeyService->getKeysForUser($user),
        );

        return $this->json($keys);
    }

    #[Route('/api-keys', name: '_create_api_key', methods: ['POST'])]
    public function createApiKey(#[MapRequestPayload] CreateApiKeyRequest $dto): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $permissions = array_map(fn (string $v) => ApiKeyPermission::from($v), $dto->permissions);
        $key = $this->apiKeyService->createKey($user, $dto->name, $dto->description ?: null, $permissions);

        return $this->json(ApiKeyResponse::fromEntity($key, includeFullKey: true), Response::HTTP_CREATED);
    }

    #[Route('/api-keys/{keyId}', name: '_revoke_api_key', methods: ['DELETE'])]
    public function revokeApiKey(int $keyId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $key = $this->apiKeyService->getKeyById($keyId);

        if ($key->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('This key does not belong to you.');
        }

        $this->apiKeyService->revokeKey($key);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
