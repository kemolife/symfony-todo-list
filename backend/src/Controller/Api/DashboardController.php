<?php

namespace App\Controller\Api;

use App\DTO\Response\AuditLogResponse;
use App\Enum\UserRole;
use App\Service\AuditLogService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Dashboard')]
#[Route('/api/dashboard', name: 'api_dashboard')]
#[IsGranted(UserRole::Admin->value)]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    #[OA\Get(
        summary: 'Dashboard overview',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard data', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
        ]
    )]
    #[Route('/', name: 'api_dashboard_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(['message' => 'Dashboard']);
    }

    #[OA\Get(
        summary: 'Recent audit activity',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by action type'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Recent audit log entries', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: AuditLogResponse::class)))),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Admin role required'),
        ]
    )]
    #[Route('/activity', name: 'api_dashboard_activity', methods: ['GET'])]
    public function activity(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        $action = $request->query->get('action');

        return $this->json($this->auditLogService->findRecent($limit, $action));
    }
}
