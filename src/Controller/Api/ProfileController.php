<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Enum\UserRole;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/profile', name: 'api_profile')]
#[IsGranted(UserRole::User->value)]
final class ProfileController extends AbstractController
{
    #[Route('', name: '_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['hasApiKey' => null !== $user->getApiKey()]);
    }

    #[Route('/api-key', name: '_generate_api_key', methods: ['POST'])]
    public function generateApiKey(UserService $userService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $userService->generateApiKey($user);

        return $this->json(['apiKey' => $user->getApiKey()], Response::HTTP_CREATED);
    }

    #[Route('/api-key', name: '_revoke_api_key', methods: ['DELETE'])]
    public function revokeApiKey(UserService $userService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $userService->revokeApiKey($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
