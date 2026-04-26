<?php

namespace App\MessageHandler;

use App\Enum\TodoStatus;
use App\Message\MarkListItemsCompleteMessage;
use App\Repository\TodoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MarkListItemsCompleteHandler
{
    public function __construct(
        private readonly TodoListRepository $todoListRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(MarkListItemsCompleteMessage $message): void
    {
        $list = $this->todoListRepository->find($message->getTodoListId());
        if (null === $list) {
            return;
        }
        if (TodoStatus::Done !== $list->getStatus()) {
            return;
        }

        $items = $list->getTodoItems();

        foreach ($items as $item) {
            $item->setIsCompleted(true);
        }

        $this->em->flush();
    }
}
