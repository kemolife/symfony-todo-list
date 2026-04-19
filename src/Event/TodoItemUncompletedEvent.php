<?php

namespace App\Event;

use App\Entity\TodoItem;
use Symfony\Contracts\EventDispatcher\Event;

class TodoItemUncompletedEvent extends Event
{
    public function __construct(
        private readonly TodoItem $todoItem,
    ) {
    }

    public function getTodoItem(): TodoItem
    {
        return $this->todoItem;
    }
}
