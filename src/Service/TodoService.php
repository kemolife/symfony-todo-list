<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\TodoRequest;
use App\DTO\Response\AdminTodoResponse;
use App\DTO\Response\PaginatedTodoResponse;
use App\DTO\Response\TodoResponse;
use App\Entity\TodoList;
use App\Entity\User;
use App\Event\TodoListStatusChangedEvent;
use App\Repository\TodoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TodoService
{
    public function __construct(
        private readonly TodoListRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function findAll(?string $status, ?string $tag, ?string $search, int $page = 1, int $limit = 10, ?User $owner = null): PaginatedTodoResponse
    {
        $total = $this->repository->countFiltered($status, $tag, $search, $owner);
        $items = array_map(
            TodoResponse::fromEntity(...),
            $this->repository->findFiltered($status, $tag, $search, $page, $limit, $owner),
        );

        return new PaginatedTodoResponse(
            items: $items,
            total: $total,
            page: $page,
            limit: $limit,
            pages: $total > 0 ? (int) ceil($total / $limit) : 1,
        );
    }

    public function findOne(int $id): TodoResponse
    {
        return TodoResponse::fromEntity($this->findOrFail($id));
    }

    public function create(TodoRequest $dto, ?User $owner = null): TodoResponse
    {
        $todo = new TodoList()
            ->setName($dto->name)
            ->setDescription($dto->description)
            ->setTag($dto->tag)
            ->setOwner($owner);

        if (null !== $dto->status) {
            $todo->setStatus($dto->status);
        }

        $this->em->persist($todo);
        $this->em->flush();

        return TodoResponse::fromEntity($todo);
    }

    public function update(int $id, TodoRequest $dto): TodoResponse
    {
        $todo = $this->findOrFail($id);

        $todo->setName($dto->name)
            ->setDescription($dto->description)
            ->setTag($dto->tag);

        if (null !== $dto->status) {
            $previousStatus = $todo->getStatus();
            $todo->setStatus($dto->status);
            if ($previousStatus !== $dto->status) {
                $this->eventDispatcher->dispatch(
                    new TodoListStatusChangedEvent($todo, $previousStatus)
                );
            }
        }

        $this->em->flush();

        return TodoResponse::fromEntity($todo);
    }

    public function delete(int $id): void
    {
        $todo = $this->findOrFail($id);
        $this->em->remove($todo);
        $this->em->flush();
    }

    public function findAllForAdmin(?int $userId, ?string $status, int $page, int $limit): PaginatedTodoResponse
    {
        $includeDeleted = 'deleted' === $status;

        if ($includeDeleted) {
            $this->em->getFilters()->disable('softdeleteable');
        }

        try {
            $total = $this->repository->countAllAdmin($userId, $status, $includeDeleted);
            $items = array_map(
                AdminTodoResponse::fromEntity(...),
                $this->repository->findAllAdmin($userId, $status, $page, $limit, $includeDeleted),
            );
        } finally {
            if ($includeDeleted) {
                $this->em->getFilters()->enable('softdeleteable');
            }
        }

        return new PaginatedTodoResponse(
            items: $items,
            total: $total,
            page: $page,
            limit: $limit,
            pages: $total > 0 ? (int) ceil($total / $limit) : 1,
        );
    }

    /** @return string[] */
    public function findAllTags(?User $owner = null): array
    {
        return $this->repository->findAllTags($owner);
    }

    public function getEntity(int $id): TodoList
    {
        return $this->findOrFail($id);
    }

    private function findOrFail(int $id): TodoList
    {
        return $this->repository->find($id)
            ?? throw new NotFoundHttpException("Todo #$id not found");
    }
}
