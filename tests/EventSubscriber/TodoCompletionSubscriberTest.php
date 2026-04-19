<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Entity\User;
use App\Enum\TodoStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TodoCompletionSubscriberTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->user = new User();
        $this->user->setEmail('subscriber@test.com');
        $this->user->setRoles(['ROLE_USER']);
        $this->user->setPassword('$2y$04$'.str_repeat('a', 53));
        $this->em->persist($this->user);
        $this->em->flush();

        $this->client->loginUser($this->user);
    }

    private function createList(string $name = 'Test list', TodoStatus $status = TodoStatus::Pending): TodoList
    {
        $list = (new TodoList())
            ->setName($name)
            ->setStatus($status)
            ->setOwner($this->user);
        $this->em->persist($list);
        $this->em->flush();

        return $list;
    }

    private function createItem(TodoList $list, string $title = 'Item', bool $completed = false): TodoItem
    {
        $item = (new TodoItem())
            ->setTitle($title)
            ->setIsCompleted($completed);
        $list->addTodoItem($item); // keeps in-memory collection in sync
        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    private function patchItem(int $listId, int $itemId, array $body): void
    {
        $this->client->request(
            'PATCH',
            "/api/todos/$listId/items/$itemId",
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode($body),
        );
    }

    private function putList(int $listId, string $name, string $status): void
    {
        $this->client->request(
            'PUT',
            "/api/todos/$listId",
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => $name, 'status' => $status]),
        );
    }

    /** Re-fetch a TodoList from DB, bypassing the EM identity map. */
    private function freshList(int $id): TodoList
    {
        $this->em->clear();

        return $this->em->find(TodoList::class, $id);
    }

    /** Re-fetch a TodoItem from DB, bypassing the EM identity map. */
    private function freshItem(int $id): TodoItem
    {
        $this->em->clear();

        return $this->em->find(TodoItem::class, $id);
    }

    // -----------------------------------------------------------------------
    // Direction 1: all items done → list auto-completes
    // -----------------------------------------------------------------------

    public function testCompletingLastItemAutoCompletesTheList(): void
    {
        $list = $this->createList();
        $item1 = $this->createItem($list, 'Item 1');
        $item2 = $this->createItem($list, 'Item 2');

        $listId = $list->getId();
        $item1Id = $item1->getId();
        $item2Id = $item2->getId();

        // Complete only the first item — list must stay pending
        $this->patchItem($listId, $item1Id, ['isCompleted' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame(TodoStatus::Pending, $this->freshList($listId)->getStatus());

        // Complete the last item — list must auto-promote to Done
        $this->patchItem($listId, $item2Id, ['isCompleted' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame(TodoStatus::Done, $this->freshList($listId)->getStatus());
    }

    public function testCompletingOneOfManyItemsDoesNotCompleteList(): void
    {
        $list = $this->createList('Multi-item list', TodoStatus::InProgress);
        $item1 = $this->createItem($list, 'Item 1');
        $this->createItem($list, 'Item 2');

        $listId = $list->getId();

        $this->patchItem($listId, $item1->getId(), ['isCompleted' => true]);
        self::assertResponseIsSuccessful();
        self::assertSame(TodoStatus::InProgress, $this->freshList($listId)->getStatus());
    }

    public function testListWithNoItemsDoesNotAutoComplete(): void
    {
        $list = $this->createList('Empty list');
        $listId = $list->getId();

        // Toggling status without items must never trigger auto-Done
        $this->putList($listId, 'Empty list', 'in_progress');
        self::assertResponseIsSuccessful();

        $this->putList($listId, 'Empty list', 'pending');
        self::assertResponseIsSuccessful();

        self::assertSame(TodoStatus::Pending, $this->freshList($listId)->getStatus());
    }

    // -----------------------------------------------------------------------
    // Direction 2: list marked Done → all items completed
    // -----------------------------------------------------------------------

    public function testMarkingListDoneCompletesAllItems(): void
    {
        $list = $this->createList('List to complete');
        $item1 = $this->createItem($list, 'Item 1');
        $item2 = $this->createItem($list, 'Item 2');
        $item3 = $this->createItem($list, 'Item 3');

        $item1Id = $item1->getId();
        $item2Id = $item2->getId();
        $item3Id = $item3->getId();
        $listId = $list->getId();

        $this->putList($listId, $list->getName(), 'done');
        self::assertResponseIsSuccessful();

        $this->em->clear();
        self::assertTrue($this->em->find(TodoItem::class, $item1Id)->isCompleted());
        self::assertTrue($this->em->find(TodoItem::class, $item2Id)->isCompleted());
        self::assertTrue($this->em->find(TodoItem::class, $item3Id)->isCompleted());
    }

    public function testMarkingListDoneWhenAlreadyDoneIsIdempotent(): void
    {
        $list = $this->createList('Already done', TodoStatus::Done);
        $item = $this->createItem($list, 'Item', true);

        $listId = $list->getId();
        $itemId = $item->getId();

        $this->putList($listId, $list->getName(), 'done');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->em->clear();
        self::assertTrue($this->em->find(TodoItem::class, $itemId)->isCompleted());
        self::assertSame(TodoStatus::Done, $this->em->find(TodoList::class, $listId)->getStatus());
    }

    public function testMarkingListInProgressDoesNotCompleteItems(): void
    {
        $list = $this->createList('In-progress list', TodoStatus::Pending);
        $item1 = $this->createItem($list, 'Item 1');
        $item2 = $this->createItem($list, 'Item 2');

        $item1Id = $item1->getId();
        $item2Id = $item2->getId();

        $this->putList($list->getId(), $list->getName(), 'in_progress');
        self::assertResponseIsSuccessful();

        $this->em->clear();
        self::assertFalse($this->em->find(TodoItem::class, $item1Id)->isCompleted());
        self::assertFalse($this->em->find(TodoItem::class, $item2Id)->isCompleted());
    }

    public function testRevertingListFromDoneDoesNotUnCompleteItems(): void
    {
        $list = $this->createList('Revert check', TodoStatus::Done);
        $item = $this->createItem($list, 'Done item', true);

        $itemId = $item->getId();

        $this->putList($list->getId(), $list->getName(), 'in_progress');
        self::assertResponseIsSuccessful();

        $this->em->clear();
        // Reverting list status must not un-complete already-completed items
        self::assertTrue($this->em->find(TodoItem::class, $itemId)->isCompleted());
    }

    public function testUncompletingItemRevertsListFromDoneToInProgress(): void
    {
        $list = $this->createList('Revert list', TodoStatus::Done);
        $item1 = $this->createItem($list, 'Item 1', true);
        $item2 = $this->createItem($list, 'Item 2', true);

        $listId = $list->getId();

        $this->patchItem($listId, $item1->getId(), ['isCompleted' => false]);
        self::assertResponseIsSuccessful();

        $this->em->clear();
        self::assertSame(TodoStatus::InProgress, $this->em->find(TodoList::class, $listId)->getStatus());
        self::assertFalse($this->em->find(TodoItem::class, $item1->getId())->isCompleted());
        self::assertTrue($this->em->find(TodoItem::class, $item2->getId())->isCompleted());
    }

    public function testSecondCompleteCycleAutoCompletesListAgain(): void
    {
        $list = $this->createList('Redo list');
        $item1 = $this->createItem($list, 'Item 1');
        $item2 = $this->createItem($list, 'Item 2');

        $listId = $list->getId();
        $item1Id = $item1->getId();
        $item2Id = $item2->getId();

        // First complete cycle
        $this->patchItem($listId, $item1Id, ['isCompleted' => true]);
        $this->patchItem($listId, $item2Id, ['isCompleted' => true]);
        self::assertSame(TodoStatus::Done, $this->freshList($listId)->getStatus());

        // Un-complete item 1 → list reverts
        $this->patchItem($listId, $item1Id, ['isCompleted' => false]);
        self::assertSame(TodoStatus::InProgress, $this->freshList($listId)->getStatus());

        // Second complete cycle — list must auto-promote to Done again
        $this->patchItem($listId, $item1Id, ['isCompleted' => true]);

        $this->em->clear();
        self::assertSame(TodoStatus::Done, $this->em->find(TodoList::class, $listId)->getStatus());
        self::assertTrue($this->em->find(TodoItem::class, $item1Id)->isCompleted());
        self::assertTrue($this->em->find(TodoItem::class, $item2Id)->isCompleted());
    }

    public function testPutListDoneSecondTimeSetsAllItemsCompleted(): void
    {
        $list = $this->createList('Put redo list');
        $item1 = $this->createItem($list, 'Item 1');
        $item2 = $this->createItem($list, 'Item 2');
        $item3 = $this->createItem($list, 'Item 3');

        $listId = $list->getId();
        $item1Id = $item1->getId();
        $item2Id = $item2->getId();
        $item3Id = $item3->getId();

        // First PUT → Done: all items auto-completed
        $this->putList($listId, $list->getName(), 'done');
        self::assertResponseIsSuccessful();
        $this->em->clear();
        self::assertTrue($this->em->find(TodoItem::class, $item1Id)->isCompleted());
        self::assertTrue($this->em->find(TodoItem::class, $item2Id)->isCompleted());
        self::assertTrue($this->em->find(TodoItem::class, $item3Id)->isCompleted());

        // Revert to in_progress, then un-complete one item
        $this->putList($listId, $list->getName(), 'in_progress');
        $this->patchItem($listId, $item1Id, ['isCompleted' => false]);

        // Second PUT → Done: ALL items must be completed again
        $this->putList($listId, $list->getName(), 'done');
        self::assertResponseIsSuccessful();

        $this->em->clear();
        self::assertSame(TodoStatus::Done, $this->em->find(TodoList::class, $listId)->getStatus());
        self::assertTrue($this->em->find(TodoItem::class, $item1Id)->isCompleted(), 'item1 must be completed');
        self::assertTrue($this->em->find(TodoItem::class, $item2Id)->isCompleted(), 'item2 must be completed');
        self::assertTrue($this->em->find(TodoItem::class, $item3Id)->isCompleted(), 'item3 must be completed');
    }

    public function testStaleHandlerMessageIsIgnoredWhenListNoLongerDone(): void
    {
        $list = $this->createList('Stale message list', TodoStatus::InProgress);
        $item1 = $this->createItem($list, 'Item 1', false);
        $item2 = $this->createItem($list, 'Item 2', false);

        $item1Id = $item1->getId();
        $item2Id = $item2->getId();

        // Simulate stale message: handler runs but list is not Done
        $handler = static::getContainer()->get(\App\MessageHandler\MarkListItemsCompleteHandler::class);
        $handler(new \App\Message\MarkListItemsCompleteMessage($list->getId()));

        $this->em->clear();
        self::assertFalse($this->em->find(TodoItem::class, $item1Id)->isCompleted());
        self::assertFalse($this->em->find(TodoItem::class, $item2Id)->isCompleted());
    }
}
