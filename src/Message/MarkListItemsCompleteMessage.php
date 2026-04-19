<?php

namespace App\Message;

class MarkListItemsCompleteMessage
{
    public function __construct(
        private readonly int $todoListId,
    ) {
    }

    public function getTodoListId(): int
    {
        return $this->todoListId;
    }
}
