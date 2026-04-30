<?php

namespace App\MessageHandler;

use App\Message\MarkListItemsCompleteMessage;
use App\Repository\TodoListRepository;
use App\Service\ListCompletionPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MarkListItemsCompleteHandler
{
    public function __construct(
        private readonly TodoListRepository $todoListRepository,
        private readonly EntityManagerInterface $em,
        private readonly ListCompletionPolicy $completionPolicy,
    ) {
    }

    public function __invoke(MarkListItemsCompleteMessage $message): void
    {
        $list = $this->todoListRepository->find($message->getTodoListId());
        if (null === $list) {
            return;
        }

        $this->completionPolicy->cascadeItemCompletion($list);
        $this->em->flush();
    }
}
