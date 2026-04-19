<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Event\TodoItemCompletedEvent;
use App\Event\TodoListStatusChangedEvent;
use App\Enum\TodoStatus;
use App\Event\TodoItemUncompletedEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use App\Message\MarkListItemsCompleteMessage;

class TodoCompletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TodoItemCompletedEvent::class => 'onTodoItemCompleted',
            TodoListStatusChangedEvent::class => 'onTodoListStatusChanged',
            TodoItemUncompletedEvent::class => 'onTodoItemUncompleted',
        ];
    }

    public function onTodoItemCompleted(TodoItemCompletedEvent $event): void
    {
        $list = $event->getTodoItem()->getTodoList();

        if (null === $list) return;
        $items = $list->getTodoItems();

        if ($items->isEmpty()) return;
        if ($list->getStatus() === TodoStatus::Done) return;

        foreach ($items as $item) {
            if (!$item->isCompleted()) return;
        }

        $previous = $list->getStatus();
        $list->setStatus(TodoStatus::Done);
        $this->eventDispatcher->dispatch(new TodoListStatusChangedEvent($list, $previous));
    }

    public function onTodoItemUncompleted(TodoItemUncompletedEvent $event): void
    {
        $list = $event->getTodoItem()->getTodoList();

        if (null === $list) return;
        if ($list->getStatus() !== TodoStatus::Done) return;

        $previous = $list->getStatus();
        $list->setStatus(TodoStatus::InProgress);
        $this->eventDispatcher->dispatch(new TodoListStatusChangedEvent($list, $previous));
    }

    public function onTodoListStatusChanged(TodoListStatusChangedEvent $event): void
    {
        if ($event->getPreviousStatus() === TodoStatus::Done) return;
        if ($event->getTodoList()->getStatus() !== TodoStatus::Done) return;

        $this->messageBus->dispatch(
            new MarkListItemsCompleteMessage($event->getTodoList()->getId())
        ); 
    }
}
