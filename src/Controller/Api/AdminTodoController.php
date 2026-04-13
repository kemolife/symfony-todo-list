<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\UserRole;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/todos', name: 'api_admin_todos')]
#[IsGranted(UserRole::Admin->value)]
final class AdminTodoController extends AbstractController
{
    public function __construct(private readonly TodoService $todoService)
    {
    }

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
}
