<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\TodoItem;

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
