<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\TodoItemRequest;
use App\DTO\Response\TodoItemResponse;
use App\Enum\UserRole;
use App\Security\TodoVoter;
use App\Service\TodoItemService;
use App\Service\TodoService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Todo Items')]
#[Route('/api/todos/{id}/items', name: 'api_todo_items')]
#[IsGranted(UserRole::User->value)]
final class TodoItemController extends AbstractController
{
    public function __construct(
        private readonly TodoItemService $todoItemService,
        private readonly TodoService $todoService,
    ) {
    }

    #[OA\Get(
        summary: 'List items of a todo list',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Todo list ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of items', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: TodoItemResponse::class)))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Todo not found'),
        ]
    )]
    #[Route('', name: '_list', methods: ['GET'])]
    public function list(int $id): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::READ, $todo);

        return $this->json($this->todoItemService->findAllByTodoListId($id));
    }

    #[OA\Post(
        summary: 'Add item to a todo list',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Todo list ID'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: TodoItemRequest::class))),
        responses: [
            new OA\Response(response: 201, description: 'Item created', content: new OA\JsonContent(ref: new Model(type: TodoItemResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Todo not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('', name: '_create', methods: ['POST'])]
    public function create(int $id, #[MapRequestPayload(validationGroups: ['create'])] TodoItemRequest $dto): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        return $this->json($this->todoItemService->create($todo, $dto), Response::HTTP_CREATED);
    }

    #[OA\Patch(
        summary: 'Update a todo item (toggle completion, reorder)',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Todo list ID'),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: TodoItemRequest::class))),
        responses: [
            new OA\Response(response: 200, description: 'Item updated', content: new OA\JsonContent(ref: new Model(type: TodoItemResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    #[Route('/{itemId}', name: '_update', methods: ['PATCH'])]
    public function update(int $id, int $itemId, #[MapRequestPayload] TodoItemRequest $dto): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        return $this->json($this->todoItemService->update($itemId, $id, $dto));
    }

    #[OA\Delete(
        summary: 'Delete a todo item',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Todo list ID'),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    #[Route('/{itemId}', name: '_delete', methods: ['DELETE'])]
    public function delete(int $id, int $itemId): Response
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        $this->todoItemService->delete($itemId, $id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
