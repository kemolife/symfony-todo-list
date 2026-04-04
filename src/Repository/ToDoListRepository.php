<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ToDoList;
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
    public function findFiltered(?string $status, ?string $tag, ?string $search, int $page = 1, int $limit = 10): array
    {
        return $this->buildFilteredQuery($status, $tag, $search)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(?string $status, ?string $tag, ?string $search): int
    {
        return (int) $this->buildFilteredQuery($status, $tag, $search)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function findAllTags(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.tag')
            ->where('t.tag IS NOT NULL')
            ->orderBy('t.tag', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    private function buildFilteredQuery(?string $status, ?string $tag, ?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

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
