<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\TodoList;
use App\Entity\User;
use App\Enum\TodoStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TodoControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        // Clear todo cache so DAMA DB rollbacks don't leave stale cache entries
        static::getContainer()->get('cache.todo')->invalidateTags(['todos']);
    }

    private function createUser(string $email = 'user@test.com', string $role = 'ROLE_USER'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        // loginUser() bypasses password verification; any non-empty hash works
        $user->setPassword('$2y$04$'.str_repeat('a', 53));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }

    private function createTodo(string $name = 'Test todo', ?string $tag = null, TodoStatus $status = TodoStatus::Pending, ?User $owner = null): TodoList
    {
        $todo = (new TodoList())
            ->setName($name)
            ->setTag($tag)
            ->setStatus($status)
            ->setOwner($owner);

        $this->em->persist($todo);
        $this->em->flush();

        return $todo;
    }

    public function testListAll(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->createTodo('First', owner: $user);
        $this->createTodo('Second', owner: $user);

        $this->client->request('GET', '/api/todos');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('page', $data);
        self::assertArrayHasKey('limit', $data);
        self::assertArrayHasKey('pages', $data);
        self::assertCount(2, $data['items']);
        self::assertSame(2, $data['total']);
        self::assertSame(1, $data['page']);
    }

    public function testListPagination(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        for ($i = 1; $i <= 15; ++$i) {
            $this->createTodo("Todo $i", owner: $user);
        }

        $this->client->request('GET', '/api/todos?page=2&limit=10');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(5, $data['items']);
        self::assertSame(15, $data['total']);
        self::assertSame(2, $data['page']);
        self::assertSame(2, $data['pages']);
    }

    public function testListFilterByStatus(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->createTodo('Done todo', status: TodoStatus::Done, owner: $user);
        $this->createTodo('Pending todo', status: TodoStatus::Pending, owner: $user);

        $this->client->request('GET', '/api/todos?status=done');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data['items']);
        self::assertSame('done', $data['items'][0]['status']);
    }

    public function testListFilterByTag(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->createTodo('Work todo', tag: 'work', owner: $user);
        $this->createTodo('Personal todo', tag: 'personal', owner: $user);

        $this->client->request('GET', '/api/todos?tag=work');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data['items']);
        self::assertSame('work', $data['items'][0]['tag']);
    }

    public function testListFilterBySearch(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->createTodo('Buy groceries', owner: $user);
        $this->createTodo('Call dentist', owner: $user);

        $this->client->request('GET', '/api/todos?search=groceries');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data['items']);
        self::assertSame('Buy groceries', $data['items'][0]['name']);
    }

    public function testGetOne(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $todo = $this->createTodo('Single todo', owner: $user);

        $this->client->request('GET', '/api/todos/'.$todo->getId());

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Single todo', $data['name']);
        self::assertSame('pending', $data['status']);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('updatedAt', $data);
    }

    public function testGetOneNotFound(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->client->request('GET', '/api/todos/99999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testCreate(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'New todo',
            'description' => 'A description',
            'tag' => 'work',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('New todo', $data['name']);
        self::assertSame('pending', $data['status']);
        self::assertSame('work', $data['tag']);
    }

    public function testCreateValidationFailure(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => '',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdate(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $todo = $this->createTodo('Old name', owner: $user);

        $this->client->request('PUT', '/api/todos/'.$todo->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'New name',
            'status' => 'done',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('New name', $data['name']);
        self::assertSame('done', $data['status']);
    }

    public function testDelete(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $todo = $this->createTodo('To delete', owner: $user);
        $id = $todo->getId();

        $this->client->request('DELETE', '/api/todos/'.$id);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->client->request('GET', '/api/todos/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetTags(): void
    {
        $user = $this->createUser();
        $this->loginAs($user);
        $this->createTodo('A', tag: 'work', owner: $user);
        $this->createTodo('B', tag: 'personal', owner: $user);
        $this->createTodo('C', tag: 'work', owner: $user);

        $this->client->request('GET', '/api/todos/tags');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertContains('work', $data);
        self::assertContains('personal', $data);
        self::assertCount(2, $data);
    }

    public function testUnauthenticatedRequestIsRejected(): void
    {
        $this->client->request('GET', '/api/todos');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonOwnerCannotUpdate(): void
    {
        $owner = $this->createUser('owner@test.com');
        $other = $this->createUser('other@test.com');
        $todo = $this->createTodo('Owned todo', owner: $owner);

        $this->loginAs($other);
        $this->client->request('PUT', '/api/todos/'.$todo->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Hijacked',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNonOwnerCannotDelete(): void
    {
        $owner = $this->createUser('owner2@test.com');
        $other = $this->createUser('other2@test.com');
        $todo = $this->createTodo('Owned todo', owner: $owner);

        $this->loginAs($other);
        $this->client->request('DELETE', '/api/todos/'.$todo->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanUpdateAnyTodo(): void
    {
        $owner = $this->createUser('owner3@test.com');
        $admin = $this->createUser('admin@test.com', 'ROLE_ADMIN');
        $todo = $this->createTodo('Admin target', owner: $owner);

        $this->loginAs($admin);
        $this->client->request('PUT', '/api/todos/'.$todo->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Updated by admin',
        ]));

        self::assertResponseIsSuccessful();
    }

    public function testCreateTodoDefaultPriorityIsMedium(): void
    {
        $user = $this->createUser('priority1@test.com');
        $this->loginAs($user);
        $this->client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Priority Test']));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('medium', $data['priority']);
        self::assertNull($data['dueDate']);
    }

    public function testCreateTodoWithHighPriorityAndDueDate(): void
    {
        $user = $this->createUser('priority2@test.com');
        $this->loginAs($user);
        $this->client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name'     => 'Urgent task',
            'priority' => 'high',
            'dueDate'  => '2030-12-31',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('high', $data['priority']);
        self::assertSame('2030-12-31', $data['dueDate']);
    }

    public function testTodosOrderedByPriority(): void
    {
        $user = $this->createUser('priority3@test.com');

        foreach (['low' => \App\Enum\TodoPriority::Low, 'high' => \App\Enum\TodoPriority::High, 'medium' => \App\Enum\TodoPriority::Medium] as $label => $priority) {
            $todo = (new TodoList())->setName("$label task")->setPriority($priority)->setOwner($user);
            $this->em->persist($todo);
        }
        $this->em->flush();

        $this->loginAs($user);
        $this->client->request('GET', '/api/todos');
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertCount(3, $data['items']);
        self::assertSame('high',   $data['items'][0]['priority']);
        self::assertSame('medium', $data['items'][1]['priority']);
        self::assertSame('low',    $data['items'][2]['priority']);
    }
}
