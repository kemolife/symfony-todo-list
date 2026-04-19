<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\TodoList;
use App\Enum\TodoStatus;
use App\Message\MoveDoneTodosToDeletedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MoveDoneTodosToDeletedHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(MoveDoneTodosToDeletedMessage $message): void
    {
        $query = $this->em->createQueryBuilder()
            ->select('t')
            ->from(TodoList::class, 't')
            ->where('t.status = :status')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('status', TodoStatus::Done->value)
            ->getQuery();

        foreach ($query->toIterable() as $todo) {
            $this->em->remove($todo);
        }

        $this->em->flush();
    }
}
