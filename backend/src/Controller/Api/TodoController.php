<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\CsvColumnMap;
use App\DTO\Request\TodoRequest;
use App\DTO\Response\ImportResult;
use App\DTO\Response\PaginatedTodoResponse;
use App\DTO\Response\TodoResponse;
use App\Entity\User;
use App\Enum\UserRole;
use App\Security\TodoVoter;
use App\Service\CsvImportService;
use App\Service\TodoService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Todos')]
#[Route('/api/todos', name: 'api_todos')]
#[IsGranted(UserRole::User->value)]
final class TodoController extends AbstractController
{
    public function __construct(
        private readonly TodoService $todoService,
        private readonly CsvImportService $csvImportService,
    ) {
    }

    // IMPORTANT: /tags and /import must be declared BEFORE /{id} to avoid routing conflict
    #[OA\Get(
        summary: 'List unique tags for current user',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Array of tag strings', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'string', example: 'work'))),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    #[Route('/tags', name: '_tags', methods: ['GET'])]
    public function tags(): JsonResponse
    {
        $this->denyAccessUnlessGranted(TodoVoter::READ, null);

        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->todoService->findAllTags($user));
    }

    #[OA\Post(
        summary: 'Import todos from CSV file',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file'),
                        new OA\Property(property: 'columnMap', type: 'string', description: 'JSON column mapping, e.g. {"name":"Title","tag":"Category"}'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Import result', content: new OA\JsonContent(ref: new Model(type: ImportResult::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Invalid file'),
        ]
    )]
    #[Route('/import', name: '_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(TodoVoter::CREATE, null);

        /** @var User $user */
        $user = $this->getUser();

        $mapData = json_decode($request->request->get('columnMap', '{}'), true) ?? [];
        $map = CsvColumnMap::fromArray($mapData);

        $result = $this->csvImportService->parser($request->files->get('file'), $user, $map);

        return $this->json($result);
    }

    #[OA\Get(
        summary: 'List todos (paginated)',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'done'])),
            new OA\Parameter(name: 'tag', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'dueDateFilter', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['overdue', 'today', 'this_week'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated todo list', content: new OA\JsonContent(ref: new Model(type: PaginatedTodoResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    #[Route('', name: '_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(TodoVoter::READ, null);

        /** @var User $user */
        $user = $this->getUser();
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        return $this->json($this->todoService->findAll(
            status: $request->query->get('status'),
            tag: $request->query->get('tag'),
            search: $request->query->get('search'),
            page: $page,
            limit: $limit,
            owner: $user,
            dueDateFilter: $request->query->get('dueDateFilter'),
        ));
    }

    #[OA\Post(
        summary: 'Create a todo list',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: TodoRequest::class))),
        responses: [
            new OA\Response(response: 201, description: 'Todo created', content: new OA\JsonContent(ref: new Model(type: TodoResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('', name: '_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TodoRequest $dto): JsonResponse
    {
        $this->denyAccessUnlessGranted(TodoVoter::CREATE, null);

        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->todoService->create($dto, $user), Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: 'Get a todo list',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Todo with items', content: new OA\JsonContent(ref: new Model(type: TodoResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    #[Route('/{id}', name: '_one', methods: ['GET'])]
    public function one(int $id): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::READ, $todo);

        return $this->json(TodoResponse::fromEntity($todo));
    }

    #[OA\Put(
        summary: 'Update a todo list',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: TodoRequest::class))),
        responses: [
            new OA\Response(response: 200, description: 'Updated todo', content: new OA\JsonContent(ref: new Model(type: TodoResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[Route('/{id}', name: '_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] TodoRequest $dto): JsonResponse
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::EDIT, $todo);

        return $this->json($this->todoService->update($id, $dto));
    }

    #[OA\Delete(
        summary: 'Delete a todo list',
        security: [['bearerAuth' => []], ['apiKey' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not your todo'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    #[Route('/{id}', name: '_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $todo = $this->todoService->getEntity($id);
        $this->denyAccessUnlessGranted(TodoVoter::DELETE, $todo);

        $this->todoService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
