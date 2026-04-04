# Symfony API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete REST API for todo items with CRUD, filtering, and tag listing using Symfony 7.4, Doctrine ORM, and Symfony Serializer.

**Architecture:** Thin controllers delegate to a `TodoService` that calls a `ToDoListRepository`. Requests are deserialized into Request DTOs and validated. Responses are serialized from Response DTOs — never from raw entities. An `ExceptionListener` converts all exceptions to consistent JSON.

**Tech Stack:** PHP 8.2, Symfony 7.4, Doctrine ORM 3.x, Symfony Serializer, Symfony Validator, NelmioCorsBundle, PHPUnit + WebTestCase + dama/doctrine-test-bundle

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `src/Enum/TodoStatus.php` | Backed enum for todo status values |
| Modify | `src/Entity/ToDoList.php` | Add `status`, `updatedAt`, lifecycle callback |
| Create | `migrations/Version<ts>.php` | Add `status`, `updated_at` columns |
| Modify | `src/DataFixtures/ToDoListFixture.php` | Set random status on fixtures |
| Create | `src/DTO/Request/TodoRequest.php` | Input DTO, validated |
| Create | `src/DTO/Response/TodoResponse.php` | Output DTO, never expose entity directly |
| Modify | `src/Repository/ToDoListRepository.php` | `findFiltered()`, `findAllTags()` |
| Create | `src/Service/TodoService.php` | All business logic |
| Create | `src/EventListener/ExceptionListener.php` | Converts exceptions to JSON |
| Create | `src/Controller/Api/TodoController.php` | 6 endpoints, correct HTTP verbs/status codes |
| Delete | `src/Controller/MainController.php` | Replaced by TodoController |
| Create | `config/packages/nelmio_cors.yaml` | CORS config for React dev server |
| Modify | `.env` | Add `CORS_ALLOW_ORIGIN` |
| Create | `phpunit.xml.dist` | PHPUnit config with DAMA extension |
| Create | `tests/bootstrap.php` | Test bootstrap |
| Create | `tests/Controller/Api/TodoControllerTest.php` | WebTestCase for all 6 endpoints |

---

## Task 1: Install Test Infrastructure

**Files:**
- Create: `phpunit.xml.dist`
- Create: `tests/bootstrap.php`

- [ ] **Step 1: Install packages**

```bash
composer require --dev symfony/test-pack dama/doctrine-test-bundle
```

Expected: packages installed, `phpunit.xml.dist` created by symfony/test-pack.

- [ ] **Step 2: Replace phpunit.xml.dist with correct config**

Overwrite `phpunit.xml.dist` with:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
>
    <php>
        <ini name="display_errors" value="1"/>
        <ini name="error_reporting" value="-1"/>
        <server name="APP_ENV" value="test" force="true"/>
        <server name="SHELL_VERBOSITY" value="-1"/>
        <server name="SYMFONY_PHPUNIT_REMOVE" value=""/>
        <server name="SYMFONY_PHPUNIT_VERSION" value="11.4"/>
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <extensions>
        <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
    </extensions>

    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 3: Create tests/bootstrap.php**

```php
<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
```

- [ ] **Step 4: Create and migrate test database**

```bash
APP_ENV=test php bin/console doctrine:database:create
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
```

Expected output ends with: `[OK] Successfully executed X migrations`

- [ ] **Step 5: Run PHPUnit to confirm setup works**

```bash
./vendor/bin/phpunit --list-tests
```

Expected: `No tests found` (no tests yet — that's fine).

- [ ] **Step 6: Commit**

```bash
git add phpunit.xml.dist tests/bootstrap.php composer.json composer.lock symfony.lock
git commit -m "feat: add PHPUnit + DAMA doctrine test infrastructure"
```

---

## Task 2: TodoStatus Enum

**Files:**
- Create: `src/Enum/TodoStatus.php`

- [ ] **Step 1: Create the enum**

```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum TodoStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Enum/TodoStatus.php
git commit -m "feat: add TodoStatus backed enum"
```

---

## Task 3: Update Entity + Migration

**Files:**
- Modify: `src/Entity/ToDoList.php`
- Create: migration file (generated)
- Modify: `src/DataFixtures/ToDoListFixture.php`

- [ ] **Step 1: Update ToDoList entity**

Replace the full content of `src/Entity/ToDoList.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TodoStatus;
use App\Repository\ToDoListRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ToDoListRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ToDoList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tag = null;

    #[ORM\Column(enumType: TodoStatus::class)]
    private TodoStatus $status = TodoStatus::Pending;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getStatus(): TodoStatus
    {
        return $this->status;
    }

    public function setStatus(TodoStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
```

- [ ] **Step 2: Generate migration**

```bash
php bin/console make:migration
```

Expected: `created: migrations/Version<timestamp>.php`

- [ ] **Step 3: Open the generated migration and verify it adds two columns**

The migration's `up()` method should contain:
- `$this->addSql("ALTER TABLE to_do_list ADD status VARCHAR(255) NOT NULL DEFAULT 'pending'");`
- `$this->addSql("ALTER TABLE to_do_list ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()");`

If Doctrine didn't add the defaults (it may not), manually edit the migration to add `DEFAULT 'pending'` and `DEFAULT NOW()` so existing rows get valid values. The final `up()` should look like:

```php
public function up(Schema $schema): void
{
    $this->addSql("ALTER TABLE to_do_list ADD status VARCHAR(255) NOT NULL DEFAULT 'pending'");
    $this->addSql("ALTER TABLE to_do_list ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()");
    $this->addSql("COMMENT ON COLUMN to_do_list.status IS '(DC2Type:todo_status)'");
    $this->addSql("COMMENT ON COLUMN to_do_list.updated_at IS '(DC2Type:datetime_immutable)'");
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE to_do_list DROP status');
    $this->addSql('ALTER TABLE to_do_list DROP updated_at');
}
```

- [ ] **Step 4: Run migration on dev and test databases**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 5: Update fixture to set random status**

Replace `src/DataFixtures/ToDoListFixture.php`:

```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ToDoList;
use App\Enum\TodoStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class ToDoListFixture extends Fixture
{
    protected Generator $faker;

    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create();
        $statuses = TodoStatus::cases();

        for ($i = 0; $i < 20; ++$i) {
            $list = (new ToDoList())
                ->setName($this->faker->sentence())
                ->setDescription($this->faker->paragraph())
                ->setTag($this->faker->randomElement(['work', 'personal', 'shopping', 'health']))
                ->setStatus($this->faker->randomElement($statuses));

            $manager->persist($list);
        }

        $manager->flush();
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add src/Entity/ToDoList.php src/DataFixtures/ToDoListFixture.php migrations/
git commit -m "feat: add status enum and updatedAt to ToDoList entity"
```

---

## Task 4: Response DTO

**Files:**
- Create: `src/DTO/Response/TodoResponse.php`

- [ ] **Step 1: Create directory and DTO**

```bash
mkdir -p src/DTO/Response
```

Create `src/DTO/Response/TodoResponse.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\ToDoList;

final readonly class TodoResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $tag,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(ToDoList $todo): self
    {
        return new self(
            id: $todo->getId(),
            name: $todo->getName(),
            description: $todo->getDescription(),
            tag: $todo->getTag(),
            status: $todo->getStatus()->value,
            createdAt: $todo->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $todo->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/DTO/
git commit -m "feat: add TodoResponse DTO"
```

---

## Task 5: Request DTO

**Files:**
- Create: `src/DTO/Request/TodoRequest.php`

- [ ] **Step 1: Create DTO**

```bash
mkdir -p src/DTO/Request
```

Create `src/DTO/Request/TodoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Enum\TodoStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class TodoRequest
{
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[Assert\Length(max: 100)]
    public ?string $tag = null;

    public ?TodoStatus $status = null;
}
```

- [ ] **Step 2: Commit**

```bash
git add src/DTO/Request/
git commit -m "feat: add TodoRequest DTO with validation constraints"
```

---

## Task 6: Repository Filter Methods

**Files:**
- Modify: `src/Repository/ToDoListRepository.php`

- [ ] **Step 1: Add filter methods**

Replace `src/Repository/ToDoListRepository.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ToDoList;
use App\Enum\TodoStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ToDoList>
 */
final class ToDoListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDoList::class);
    }

    /**
     * @return ToDoList[]
     */
    public function findFiltered(?string $status, ?string $tag, ?string $search): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($status !== null && TodoStatus::tryFrom($status) !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if ($tag !== null && $tag !== '') {
            $qb->andWhere('t.tag = :tag')
                ->setParameter('tag', $tag);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->orderBy('t.createdAt', 'DESC')->getQuery()->getResult();
    }

    /**
     * @return string[]
     */
    public function findAllTags(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.tag')
            ->where('t.tag IS NOT NULL')
            ->orderBy('t.tag', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Repository/ToDoListRepository.php
git commit -m "feat: add findFiltered and findAllTags to repository"
```

---

## Task 7: TodoService

**Files:**
- Create: `src/Service/TodoService.php`

- [ ] **Step 1: Create service**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\TodoRequest;
use App\DTO\Response\TodoResponse;
use App\Entity\ToDoList;
use App\Repository\ToDoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TodoService
{
    public function __construct(
        private readonly ToDoListRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @return TodoResponse[]
     */
    public function findAll(?string $status, ?string $tag, ?string $search): array
    {
        return array_map(
            TodoResponse::fromEntity(...),
            $this->repository->findFiltered($status, $tag, $search),
        );
    }

    public function findOne(int $id): TodoResponse
    {
        return TodoResponse::fromEntity($this->findOrFail($id));
    }

    public function create(TodoRequest $dto): TodoResponse
    {
        $todo = (new ToDoList())
            ->setName($dto->name)
            ->setDescription($dto->description)
            ->setTag($dto->tag);

        if ($dto->status !== null) {
            $todo->setStatus($dto->status);
        }

        $this->em->persist($todo);
        $this->em->flush();

        return TodoResponse::fromEntity($todo);
    }

    public function update(int $id, TodoRequest $dto): TodoResponse
    {
        $todo = $this->findOrFail($id);

        $todo->setName($dto->name)
            ->setDescription($dto->description)
            ->setTag($dto->tag);

        if ($dto->status !== null) {
            $todo->setStatus($dto->status);
        }

        $this->em->flush();

        return TodoResponse::fromEntity($todo);
    }

    public function delete(int $id): void
    {
        $todo = $this->findOrFail($id);
        $this->em->remove($todo);
        $this->em->flush();
    }

    /** @return string[] */
    public function findAllTags(): array
    {
        return $this->repository->findAllTags();
    }

    private function findOrFail(int $id): ToDoList
    {
        return $this->repository->find($id)
            ?? throw new NotFoundHttpException("Todo #$id not found");
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Service/TodoService.php
git commit -m "feat: add TodoService with CRUD and filter methods"
```

---

## Task 8: ExceptionListener

**Files:**
- Create: `src/EventListener/ExceptionListener.php`

- [ ] **Step 1: Create listener**

Since `autoconfigure: true` is enabled in `services.yaml`, use the `#[AsEventListener]` attribute — no YAML registration needed.

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $event->setResponse(new JsonResponse(
            ['error' => $exception->getMessage()],
            $statusCode,
        ));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/EventListener/ExceptionListener.php
git commit -m "feat: add ExceptionListener for consistent JSON error responses"
```

---

## Task 9: API Controller + Tests

**Files:**
- Create: `src/Controller/Api/TodoController.php`
- Delete: `src/Controller/MainController.php`
- Create: `tests/Controller/Api/TodoControllerTest.php`

> **Routing note:** `GET /api/todos/tags` MUST be declared before `GET /api/todos/{id}` in the controller. Symfony matches routes top-to-bottom — if `{id}` comes first, it will capture the string `"tags"` as an integer and return 404.

- [ ] **Step 1: Write failing tests first**

```bash
mkdir -p tests/Controller/Api
```

Create `tests/Controller/Api/TodoControllerTest.php`:

```php
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

    protected function setUp(): void
    {
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
        $client = static::createClient();
        $this->createTodo('First');
        $this->createTodo('Second');

        $client->request('GET', '/api/todos');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
    }

    public function testListFilterByStatus(): void
    {
        $client = static::createClient();
        $this->createTodo('Done todo', status: TodoStatus::Done);
        $this->createTodo('Pending todo', status: TodoStatus::Pending);

        $client->request('GET', '/api/todos?status=done');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('done', $data[0]['status']);
    }

    public function testListFilterByTag(): void
    {
        $client = static::createClient();
        $this->createTodo('Work todo', tag: 'work');
        $this->createTodo('Personal todo', tag: 'personal');

        $client->request('GET', '/api/todos?tag=work');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('work', $data[0]['tag']);
    }

    public function testListFilterBySearch(): void
    {
        $client = static::createClient();
        $this->createTodo('Buy groceries');
        $this->createTodo('Call dentist');

        $client->request('GET', '/api/todos?search=groceries');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Buy groceries', $data[0]['name']);
    }

    public function testGetOne(): void
    {
        $client = static::createClient();
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
        $client = static::createClient();
        $client->request('GET', '/api/todos/99999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testCreate(): void
    {
        $client = static::createClient();
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
        $client = static::createClient();
        $client->request('POST', '/api/todos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => '',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdate(): void
    {
        $client = static::createClient();
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
        $client = static::createClient();
        $todo = $this->createTodo('To delete');

        $client->request('DELETE', '/api/todos/'.$todo->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/todos/'.$todo->getId());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetTags(): void
    {
        $client = static::createClient();
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
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php --testdox
```

Expected: All tests FAIL — controller does not exist yet.

- [ ] **Step 3: Create the controller**

```bash
mkdir -p src/Controller/Api
```

Create `src/Controller/Api/TodoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\TodoRequest;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/todos', name: 'api_todos')]
final class TodoController extends AbstractController
{
    public function __construct(private readonly TodoService $todoService) {}

    // IMPORTANT: /tags must be declared BEFORE /{id} to avoid routing conflict
    #[Route('/tags', name: '_tags', methods: ['GET'])]
    public function tags(): JsonResponse
    {
        return $this->json($this->todoService->findAllTags());
    }

    #[Route('', name: '_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $todos = $this->todoService->findAll(
            status: $request->query->get('status'),
            tag: $request->query->get('tag'),
            search: $request->query->get('search'),
        );

        return $this->json($todos);
    }

    #[Route('', name: '_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TodoRequest $dto): JsonResponse
    {
        return $this->json($this->todoService->create($dto), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: '_one', methods: ['GET'])]
    public function one(int $id): JsonResponse
    {
        return $this->json($this->todoService->findOne($id));
    }

    #[Route('/{id}', name: '_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] TodoRequest $dto): JsonResponse
    {
        return $this->json($this->todoService->update($id, $dto));
    }

    #[Route('/{id}', name: '_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->todoService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 4: Delete old MainController**

```bash
rm src/Controller/MainController.php
```

- [ ] **Step 5: Run tests — verify they pass**

```bash
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php --testdox
```

Expected: All tests PASS.

If `testCreateValidationFailure` fails with 400 instead of 422: `#[MapRequestPayload]` returns 422 by default in Symfony 7 — ensure you're on Symfony 7.4.

- [ ] **Step 6: Commit**

```bash
git add src/Controller/Api/ tests/Controller/
git rm src/Controller/MainController.php
git commit -m "feat: add TodoController with all endpoints + WebTestCase tests"
```

---

## Task 10: CORS Configuration

**Files:**
- Create: `config/packages/nelmio_cors.yaml`
- Modify: `.env`

- [ ] **Step 1: Install NelmioCorsBundle**

```bash
composer require nelmio/cors-bundle
```

- [ ] **Step 2: Create CORS config**

Create `config/packages/nelmio_cors.yaml`:

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization', 'Accept']
        expose_headers: []
        max_age: 3600
    paths:
        '^/api/': ~
```

- [ ] **Step 3: Add env variable to .env**

Add this line to `.env` (after the existing variables):

```
CORS_ALLOW_ORIGIN='^https?://localhost(:[0-9]+)?$'
```

- [ ] **Step 4: Run full test suite**

```bash
./vendor/bin/phpunit --testdox
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add config/packages/nelmio_cors.yaml .env composer.json composer.lock symfony.lock
git commit -m "feat: add CORS support via NelmioCorsBundle"
```

---

## Verify

```bash
# Start dev server
symfony server:start -d

# Test endpoints manually
curl http://localhost:8000/api/todos
curl -X POST http://localhost:8000/api/todos \
  -H "Content-Type: application/json" \
  -d '{"name":"My first todo","tag":"work"}'
curl http://localhost:8000/api/todos/tags
```
