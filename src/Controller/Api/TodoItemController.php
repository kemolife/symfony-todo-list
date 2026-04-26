<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\TodoItemRequest;
use App\Enum\UserRole;
use App\Security\TodoVoter;
use App\Service\TodoItemService;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/todos/{id}/items', name: 'api_todo_items')]
#[IsGranted(UserRole::User->value)]
final class TodoItemController extends AbstractController
{
    public function __construct(
        private readonly TodoItemService $todoItemService,
        private readonly TodoService $todoService,
    ) {
    }

    #[Route('', name: '_list', methods: ['GET'])]
    public function list(int $id): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::READ, $todo);

        return $this->json($this->todoItemService->findAllByTodoListId($id));
    }

    #[Route('', name: '_create', methods: ['POST'])]
    public function create(int $id, #[MapRequestPayload(validationGroups: ['create'])] TodoItemRequest $dto): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        return $this->json($this->todoItemService->create($todo, $dto), Response::HTTP_CREATED);
    }

    #[Route('/{itemId}', name: '_update', methods: ['PATCH'])]
    public function update(int $id, int $itemId, #[MapRequestPayload] TodoItemRequest $dto): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        return $this->json($this->todoItemService->update($itemId, $id, $dto));
    }

    #[Route('/{itemId}', name: '_delete', methods: ['DELETE'])]
    public function delete(int $id, int $itemId): Response
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        $this->todoItemService->delete($itemId, $id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
