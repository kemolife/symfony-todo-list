<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ToDoList;
use App\Entity\User;
use App\Enum\TodoStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ToDoList>
 */
final class ToDoListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDoList::class);
    }

    /**
     * @return ToDoList[]
     */
    public function findFiltered(?string $status, ?string $tag, ?string $search, int $page = 1, int $limit = 10, ?User $owner = null): array
    {
        return $this->buildFilteredQuery($status, $tag, $search, $owner)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(?string $status, ?string $tag, ?string $search, ?User $owner = null): int
    {
        return (int) $this->buildFilteredQuery($status, $tag, $search, $owner)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function findAllTags(?User $owner = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('DISTINCT t.tag')
            ->where('t.tag IS NOT NULL')
            ->orderBy('t.tag', 'ASC');

        if (null !== $owner) {
            $qb->andWhere('t.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * @return ToDoList[]
     */
    public function findAllAdmin(?int $userId, ?string $status, int $page, int $limit): array
    {
        return $this->buildAdminQuery($userId, $status)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAllAdmin(?int $userId, ?string $status): int
    {
        return (int) $this->buildAdminQuery($userId, $status)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildAdminQuery(?int $userId, ?string $status): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.owner', 'u');

        if (null !== $userId) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        if (null !== $status && null !== TodoStatus::tryFrom($status)) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        return $qb;
    }

    private function buildFilteredQuery(?string $status, ?string $tag, ?string $search, ?User $owner = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        if (null !== $owner) {
            $qb->andWhere('t.owner = :owner')
                ->setParameter('owner', $owner);
        }

        if (null !== $status && null !== TodoStatus::tryFrom($status)) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if (null !== $tag && '' !== $tag) {
            $qb->andWhere('t.tag = :tag')
                ->setParameter('tag', $tag);
        }

        if (null !== $search && '' !== $search) {
            $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb;
    }
}
