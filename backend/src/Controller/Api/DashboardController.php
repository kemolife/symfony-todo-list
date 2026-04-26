<?php

namespace App\Controller\Api;

use App\Enum\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard', name: 'api_dashboard')]
#[IsGranted(UserRole::Admin->value)]
final class DashboardController extends AbstractController
{
    #[Route('/', name: 'api_dashboard_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(['message' => 'Dashboard']);
    }
}
