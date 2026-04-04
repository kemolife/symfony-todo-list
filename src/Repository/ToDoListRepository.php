<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ToDoList;
use App\Enum\TodoStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findFiltered(?string $status, ?string $tag, ?string $search): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($status !== null && TodoStatus::tryFrom($status) !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if ($tag !== null && $tag !== '') {
            $qb->andWhere('t.tag = :tag')
                ->setParameter('tag', $tag);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->orderBy('t.createdAt', 'DESC')->getQuery()->getResult();
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
}
