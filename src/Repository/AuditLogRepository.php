<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Returns all audit log entries for a todo list and its items, ordered chronologically.
     *
     * @param int[] $itemIds IDs of TodoItem entities belonging to this list
     * @return AuditLog[]
     */
    public function findByTodoList(int $todoListId, array $itemIds = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.entityType = :listType AND a.entityId = :listId')
            ->setParameter('listType', 'todo_list')
            ->setParameter('listId', $todoListId);

        if (!empty($itemIds)) {
            $qb->orWhere('a.entityType = :itemType AND a.entityId IN (:itemIds)')
                ->setParameter('itemType', 'todo_item')
                ->setParameter('itemIds', $itemIds);
        }

        return $qb
            ->orderBy('a.occurredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
