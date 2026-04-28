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
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Profile')]
#[Route('/api/profile', name: 'api_profile')]
#[IsGranted(UserRole::User->value)]
final class ProfileController extends AbstractController
{
    public function __construct(private readonly ApiKeyService $apiKeyService)
    {
    }

    #[OA\Get(
        summary: 'Get own profile summary',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profile info', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'apiKeyCount', type: 'integer', example: 2)]
            )),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    #[Route('', name: '_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['apiKeyCount' => $user->getApiKeys()->count()]);
    }

    #[OA\Get(
        summary: 'List own API keys',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of API keys', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ApiKeyResponse::class)))),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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

    #[OA\Post(
        summary: 'Create an API key',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: CreateApiKeyRequest::class))),
        responses: [
            new OA\Response(response: 201, description: 'API key created — keyValue only shown once', content: new OA\JsonContent(ref: new Model(type: ApiKeyResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('/api-keys', name: '_create_api_key', methods: ['POST'])]
    public function createApiKey(#[MapRequestPayload] CreateApiKeyRequest $dto): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $permissions = array_map(fn (string $v) => ApiKeyPermission::from($v), $dto->permissions);
        $key = $this->apiKeyService->createKey($user, $dto->name, $dto->description ?: null, $permissions);

        return $this->json(ApiKeyResponse::fromEntity($key, includeFullKey: true), Response::HTTP_CREATED);
    }

    #[OA\Delete(
        summary: 'Revoke an API key',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'keyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Revoked'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Key does not belong to you'),
            new OA\Response(response: 404, description: 'Key not found'),
        ]
    )]
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
