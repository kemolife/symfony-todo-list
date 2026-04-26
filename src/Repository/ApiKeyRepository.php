<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiKey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function save(ApiKey $key): void
    {
        $this->getEntityManager()->persist($key);
        $this->getEntityManager()->flush();
    }

    public function remove(ApiKey $key): void
    {
        $this->getEntityManager()->remove($key);
        $this->getEntityManager()->flush();
    }

    public function findOneByKeyValue(string $keyValue): ?ApiKey
    {
        return $this->findOneBy(['keyValue' => $keyValue]);
    }

    /**
     * @return ApiKey[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }
}
