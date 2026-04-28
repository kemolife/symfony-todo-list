<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Response\AdminTodoResponse;
use App\DTO\Response\AuditLogResponse;
use App\DTO\Response\PaginatedTodoResponse;
use App\Enum\UserRole;
use App\Service\AuditLogService;
use App\Service\TodoService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Admin Todos')]
#[Route('/api/admin/todos', name: 'api_admin_todos')]
#[IsGranted(UserRole::Admin->value)]
final class AdminTodoController extends AbstractController
{
    public function __construct(
        private readonly TodoService $todoService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    #[OA\Get(
        summary: 'List all todos across all users (admin)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'done'])),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filter by owner user ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated admin todo list', content: new OA\JsonContent(ref: new Model(type: PaginatedTodoResponse::class))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
        ]
    )]
    #[Route('', name: '_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $userId = $request->query->has('user_id') ? $request->query->getInt('user_id') : null;

        return $this->json($this->todoService->findAllForAdmin(
            userId: $userId,
            status: $request->query->get('status'),
            page: $page,
            limit: $limit,
        ));
    }

    #[OA\Get(
        summary: 'Get audit history for a todo list',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Audit log entries', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: AuditLogResponse::class)))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'Todo not found'),
        ]
    )]
    #[Route('/{id}/history', name: '_history', methods: ['GET'])]
    public function history(int $id): JsonResponse
    {
        return $this->json($this->auditLogService->findByTodoListId($id));
    }
}
