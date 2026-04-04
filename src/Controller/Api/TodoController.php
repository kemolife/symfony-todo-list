<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\TodoRequest;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/todos', name: 'api_todos')]
final class TodoController extends AbstractController
{
    public function __construct(private readonly TodoService $todoService)
    {
    }

    // IMPORTANT: /tags must be declared BEFORE /{id} to avoid routing conflict
    #[Route('/tags', name: '_tags', methods: ['GET'])]
    public function tags(): JsonResponse
    {
        return $this->json($this->todoService->findAllTags());
    }

    #[Route('', name: '_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        return $this->json($this->todoService->findAll(
            status: $request->query->get('status'),
            tag: $request->query->get('tag'),
            search: $request->query->get('search'),
            page: $page,
            limit: $limit,
        ));
    }

    #[Route('', name: '_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TodoRequest $dto): JsonResponse
    {
        return $this->json($this->todoService->create($dto), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: '_one', methods: ['GET'])]
    public function one(int $id): JsonResponse
    {
        return $this->json($this->todoService->findOne($id));
    }

    #[Route('/{id}', name: '_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] TodoRequest $dto): JsonResponse
    {
        return $this->json($this->todoService->update($id, $dto));
    }

    #[Route('/{id}', name: '_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->todoService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
