<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\TodoItemRequest;
use App\DTO\Response\TodoItemResponse;
use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Event\TodoItemCompletedEvent;
use App\Event\TodoItemUncompletedEvent;
use App\Repository\TodoItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TodoItemService
{
    public function __construct(
        private readonly TodoItemRepository $todoItemRepository,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'cache.todo')]
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    /** @return TodoItemResponse[] */
    public function findAllByTodoListId(int $todoListId): array
    {
        $items = $this->todoItemRepository->findBy(
            ['todoList' => $todoListId],
            ['position' => 'ASC'],
        );

        return array_map(TodoItemResponse::fromEntity(...), $items);
    }

    public function findOne(int $id): TodoItem
    {
        return $this->todoItemRepository->find($id)
            ?? throw new NotFoundHttpException("Todo item #$id not found");
    }

    public function findOneForTodo(int $itemId, int $todoListId): TodoItem
    {
        $item = $this->todoItemRepository->find($itemId);

        if (null === $item || $item->getTodoList()?->getId() !== $todoListId) {
            throw new NotFoundHttpException("Todo item #$itemId not found");
        }

        return $item;
    }

    public function create(TodoList $todoList, TodoItemRequest $dto): TodoItemResponse
    {
        $item = (new TodoItem())
            ->setTitle($dto->title)
            ->setTodoList($todoList);

        if (null !== $dto->position) {
            $item->setPosition($dto->position);
        }

        $this->em->persist($item);
        $this->em->flush();
        $this->cache->invalidateTags(['todos']);

        return TodoItemResponse::fromEntity($item);
    }

    public function update(int $itemId, int $todoListId, TodoItemRequest $dto): TodoItemResponse
    {
        $item = $this->findOneForTodo($itemId, $todoListId);

        if (null !== $dto->title) {
            $item->setTitle($dto->title);
        }

        if (null !== $dto->isCompleted) {
            $item->setIsCompleted($dto->isCompleted);
            if (true === $dto->isCompleted) {
                $this->eventDispatcher->dispatch(new TodoItemCompletedEvent($item));
            } else {
                $this->eventDispatcher->dispatch(new TodoItemUncompletedEvent($item));
            }
        }

        if (null !== $dto->position) {
            $item->setPosition($dto->position);
        }

        $this->em->flush();
        $this->cache->invalidateTags(['todos']);

        return TodoItemResponse::fromEntity($item);
    }

    public function delete(int $itemId, int $todoListId): void
    {
        $item = $this->findOneForTodo($itemId, $todoListId);
        $this->em->remove($item);
        $this->em->flush();
        $this->cache->invalidateTags(['todos']);
    }
}
