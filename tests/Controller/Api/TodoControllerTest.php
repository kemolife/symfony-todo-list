<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\ToDoList;
use App\Enum\TodoStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TodoControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createTodo(string $name = 'Test todo', ?string $tag = null, TodoStatus $status = TodoStatus::Pending): ToDoList
    {
        $todo = (new ToDoList())
            ->setName($name)
            ->setTag($tag)
            ->setStatus($status);

        $this->em->persist($todo);
        $this->em->flush();

        return $todo;
    }

    public function testListAll(): void
    {
        $client = $this->client;
        $this->createTodo('First');
        $this->createTodo('Second');

        $client->request('GET', '/api/todos');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
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
        $client = $this->client;
        for ($i = 1; $i <= 15; ++$i) {
            $this->createTodo("Todo $i");
        }

        $client->request('GET', '/api/todos?page=2&limit=10');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(5, $data['items']);
        self::assertSame(15, $data['total']);
        self::assertSame(2, $data['page']);
        self::assertSame(2, $data['pages']);
    }

    public function testListFilterByStatus(): void
    {
        $client = $this->client;
        $this->createTodo('Done todo', status: TodoStatus::Done);
        $this->createTodo('Pending todo', status: TodoStatus::Pending);

        $client->request('GET', '/api/todos?status=done');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data['items']);
        self::assertSame('done', $data['items'][0]['status']);
    }

    public function testListFilterByTag(): void
    {
        $client = $this->client;
        $this->createTodo('Work todo', tag: 'work');
        $this->createTodo('Personal todo', tag: 'personal');

        $client->request('GET', '/api/todos?tag=work');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data['items']);
        self::assertSame('work', $data['items'][0]['tag']);
    }

    public function testListFilterBySearch(): void
    {
        $client = $this->client;
        $this->createTodo('Buy groceries');
        $this->createTodo('Call dentist');

        $client->request('GET', '/api/todos?search=groceries');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data['items']);
        self::assertSame('Buy groceries', $data['items'][0]['name']);
    }

    public function testGetOne(): void
    {
        $client = $this->client;
        $todo = $this->createTodo('Single todo');

        $client->request('GET', '/api/todos/'.$todo->getId());

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Single todo', $data['name']);
        self::assertSame('pending', $data['status']);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('updatedAt', $data);
    }

    public function testGetOneNotFound(): void
    {
        $client = $this->client;
        $client->request('GET', '/api/todos/99999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testCreate(): void
    {
        $client = $this->client;
        $client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'New todo',
            'description' => 'A description',
            'tag' => 'work',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('New todo', $data['name']);
        self::assertSame('pending', $data['status']);
        self::assertSame('work', $data['tag']);
    }

    public function testCreateValidationFailure(): void
    {
        $client = $this->client;
        $client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => '',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdate(): void
    {
        $client = $this->client;
        $todo = $this->createTodo('Old name');

        $client->request('PUT', '/api/todos/'.$todo->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'New name',
            'status' => 'done',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('New name', $data['name']);
        self::assertSame('done', $data['status']);
    }

    public function testDelete(): void
    {
        $client = $this->client;
        $todo = $this->createTodo('To delete');
        $id = $todo->getId();

        $client->request('DELETE', '/api/todos/'.$id);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/todos/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetTags(): void
    {
        $client = $this->client;
        $this->createTodo('A', tag: 'work');
        $this->createTodo('B', tag: 'personal');
        $this->createTodo('C', tag: 'work');

        $client->request('GET', '/api/todos/tags');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertContains('work', $data);
        self::assertContains('personal', $data);
        self::assertCount(2, $data);
    }
}
