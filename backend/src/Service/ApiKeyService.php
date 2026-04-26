<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Enum\ApiKeyPermission;
use App\Repository\ApiKeyRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;

final class ApiKeyService
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @param ApiKeyPermission[] $permissions
     */
    public function createKey(User $user, string $name, ?string $description, array $permissions): ApiKey
    {
        $key = new ApiKey();
        $key->setUser($user);
        $key->setName($name);
        $key->setDescription($description);
        $key->setKeyValue(bin2hex(random_bytes(32)));
        $key->setPermissions($permissions);
        $this->apiKeyRepository->save($key);

        return $key;
    }

    public function revokeKey(ApiKey $key): void
    {
        $this->apiKeyRepository->remove($key);

        $this->mailer->send((new \Symfony\Component\Mime\Email())
            ->from('noreply@example.com')
            ->to($key->getUser()->getEmail())
            ->subject('API Key Revoked')
            ->text(sprintf("Your API key '%s' has been revoked.", $key->getName())));
    }

    public function getKeyById(int $id): ApiKey
    {
        return $this->apiKeyRepository->find($id)
            ?? throw new NotFoundHttpException(sprintf('API key #%d not found.', $id));
    }

    /**
     * @return ApiKey[]
     */
    public function getKeysForUser(User $user): array
    {
        return $this->apiKeyRepository->findByUser($user);
    }
}
