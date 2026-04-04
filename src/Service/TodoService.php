<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\TodoRequest;
use App\DTO\Response\TodoResponse;
use App\Entity\ToDoList;
use App\Repository\ToDoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TodoService
{
    public function __construct(
        private readonly ToDoListRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @return TodoResponse[]
     */
    public function findAll(?string $status, ?string $tag, ?string $search): array
    {
        return array_map(
            TodoResponse::fromEntity(...),
            $this->repository->findFiltered($status, $tag, $search),
        );
    }

    public function findOne(int $id): TodoResponse
    {
        return TodoResponse::fromEntity($this->findOrFail($id));
    }

    public function create(TodoRequest $dto): TodoResponse
    {
        $todo = (new ToDoList())
            ->setName($dto->name)
            ->setDescription($dto->description)
            ->setTag($dto->tag);

        if ($dto->status !== null) {
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

        if ($dto->status !== null) {
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

    /** @return string[] */
    public function findAllTags(): array
    {
        return $this->repository->findAllTags();
    }

    private function findOrFail(int $id): ToDoList
    {
        return $this->repository->find($id)
            ?? throw new NotFoundHttpException("Todo #$id not found");
    }
}
