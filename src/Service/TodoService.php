<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\TodoRequest;
use App\DTO\Response\AdminTodoResponse;
use App\DTO\Response\PaginatedTodoResponse;
use App\DTO\Response\TodoResponse;
use App\Entity\ToDoList;
use App\Entity\User;
use App\Repository\ToDoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TodoService
{
    public function __construct(
        private readonly ToDoListRepository $repository,
        private readonly EntityManagerInterface $em,
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
        $todo = new ToDoList()
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
            $todo->setStatus($dto->status);
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
        $total = $this->repository->countAllAdmin($userId, $status);
        $items = array_map(
            AdminTodoResponse::fromEntity(...),
            $this->repository->findAllAdmin($userId, $status, $page, $limit),
        );

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

    public function getEntity(int $id): ToDoList
    {
        return $this->findOrFail($id);
    }

    private function findOrFail(int $id): ToDoList
    {
        return $this->repository->find($id)
            ?? throw new NotFoundHttpException("Todo #$id not found");
    }
}
