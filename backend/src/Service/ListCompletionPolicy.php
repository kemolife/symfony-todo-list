<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Enum\TodoStatus;
use App\Message\MarkListItemsCompleteMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final class ListCompletionPolicy
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function handleItemCompleted(TodoItem $item): void
    {
        $list = $item->getTodoList();

        if (null === $list) {
            return;
        }

        $items = $list->getTodoItems();

        if ($items->isEmpty()) {
            return;
        }

        foreach ($items as $listItem) {
            if (!$listItem->isCompleted()) {
                return;
            }
        }

        $this->setListStatus($list, TodoStatus::Done);
    }

    public function handleItemUncompleted(TodoItem $item): void
    {
        $list = $item->getTodoList();

        if (null === $list) {
            return;
        }

        if (TodoStatus::Done !== $list->getStatus()) {
            return;
        }

        $this->setListStatus($list, TodoStatus::InProgress);
    }

    public function setListStatus(TodoList $list, TodoStatus $newStatus): void
    {
        if ($list->getStatus() === $newStatus) {
            return;
        }

        $list->setStatus($newStatus);

        if (TodoStatus::Done === $newStatus) {
            $this->messageBus->dispatch(new MarkListItemsCompleteMessage($list->getId()));
        }
    }

    public function cascadeItemCompletion(TodoList $list): void
    {
        if (TodoStatus::Done !== $list->getStatus()) {
            return;
        }

        foreach ($list->getTodoItems() as $item) {
            $item->setIsCompleted(true);
        }
    }
}
