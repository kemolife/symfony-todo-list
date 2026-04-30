<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TodoList;
use App\Entity\User;
use App\Enum\TodoStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TodoList>
 */
final class TodoListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TodoList::class);
    }

    /**
     * @return TodoList[]
     */
    public function findFiltered(?string $status, ?string $tag, ?string $search, int $page = 1, int $limit = 10, ?User $owner = null, ?string $dueDateFilter = null): array
    {
        return $this->buildFilteredQuery($status, $tag, $search, $owner, $dueDateFilter)
            ->leftJoin('t.todoItems', 'ti')
            ->addSelect('ti')
            ->orderBy('t.priority', 'ASC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(?string $status, ?string $tag, ?string $search, ?User $owner = null, ?string $dueDateFilter = null): int
    {
        return (int) $this->buildFilteredQuery($status, $tag, $search, $owner, $dueDateFilter)
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
     * @return TodoList[]
     */
    public function findAllAdmin(?int $userId, ?string $status, int $page, int $limit, bool $includeDeleted = false): array
    {
        return $this->buildAdminQuery($userId, $status, $includeDeleted)
            ->leftJoin('t.todoItems', 'ti')
            ->addSelect('ti')
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAllAdmin(?int $userId, ?string $status, bool $includeDeleted = false): int
    {
        return (int) $this->buildAdminQuery($userId, $status, $includeDeleted)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildAdminQuery(?int $userId, ?string $status, bool $includeDeleted = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.owner', 'u');

        if (null !== $userId) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        if ($includeDeleted) {
            $qb->andWhere('t.deletedAt IS NOT NULL');
        } else {
            $this->applyStatusFilter($qb, $status);
        }

        return $qb;
    }

    private function buildFilteredQuery(?string $status, ?string $tag, ?string $search, ?User $owner = null, ?string $dueDateFilter = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        if (null !== $owner) {
            $qb->andWhere('t.owner = :owner')
                ->setParameter('owner', $owner);
        }

        $this->applyStatusFilter($qb, $status);

        if (null !== $tag && '' !== $tag) {
            $qb->andWhere('t.tag = :tag')
                ->setParameter('tag', $tag);
        }

        if (null !== $search && '' !== $search) {
            $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        $today = new \DateTimeImmutable('today');

        match ($dueDateFilter) {
            'overdue'   => $qb->andWhere('t.dueDate IS NOT NULL AND t.dueDate < :today AND t.status != :done')
                              ->setParameter('today', $today)
                              ->setParameter('done', TodoStatus::Done),
            'today'     => $qb->andWhere('t.dueDate = :today')
                              ->setParameter('today', $today),
            'this_week' => $qb->andWhere('t.dueDate >= :today AND t.dueDate <= :endOfWeek')
                              ->setParameter('today', $today)
                              ->setParameter('endOfWeek', $today->modify('+6 days')),
            default     => null,
        };

        return $qb;
    }

    private function applyStatusFilter(QueryBuilder $qb, ?string $status): void
    {
        $resolved = null !== $status ? TodoStatus::tryFrom($status) : null;
        if (null !== $resolved) {
            $qb->andWhere('t.status = :status')->setParameter('status', $resolved);
        }
    }
}
