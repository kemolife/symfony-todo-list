<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\AuditLogResponse;
use App\Repository\AuditLogRepository;
use App\Repository\TodoItemRepository;
use App\Repository\TodoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AuditLogService
{
    public function __construct(
        private readonly AuditLogRepository $auditRepo,
        private readonly TodoListRepository $todoListRepo,
        private readonly TodoItemRepository $todoItemRepo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return AuditLogResponse[]
     */
    public function findByTodoListId(int $todoListId): array
    {
        $this->em->getFilters()->disable('softdeleteable');
        try {
            $todo = $this->todoListRepo->find($todoListId);
        } finally {
            $this->em->getFilters()->enable('softdeleteable');
        }

        if (!$todo) {
            throw new NotFoundHttpException("Todo #$todoListId not found");
        }

        $itemIds = array_map(
            static fn($item) => $item->getId(),
            $this->todoItemRepo->findBy(['todoList' => $todoListId]),
        );

        return array_map(
            AuditLogResponse::fromEntity(...),
            $this->auditRepo->findByTodoList($todoListId, $itemIds),
        );
    }
}
