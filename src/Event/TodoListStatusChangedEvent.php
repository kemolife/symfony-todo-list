<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\TodoList;
use App\Enum\TodoStatus;

class TodoListStatusChangedEvent extends Event
{
    public function __construct(
        private readonly TodoList $todoList,
        private readonly TodoStatus $previousStatus,
    ) {
    }

    public function getTodoList(): TodoList
    {
        return $this->todoList;
    }

    public function getPreviousStatus(): TodoStatus
    {
        return $this->previousStatus;
    }
}
