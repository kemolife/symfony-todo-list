<?php

namespace App\Controller\Api;

use App\Enum\UserRole;
use App\Service\AuditLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard', name: 'api_dashboard')]
#[IsGranted(UserRole::Admin->value)]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    #[Route('/', name: 'api_dashboard_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(['message' => 'Dashboard']);
    }

    #[Route('/activity', name: 'api_dashboard_activity', methods: ['GET'])]
    public function activity(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        $action = $request->query->get('action');

        return $this->json($this->auditLogService->findRecent($limit, $action));
    }
}
