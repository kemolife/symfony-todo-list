# UI/UX Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform the Todo app from a basic flat list into a polished, minimal productivity app with left sidebar navigation, dark mode, priority/due-date support, and a full settings page — suitable as a fullstack portfolio demo.

**Architecture:** Backend gains `TodoPriority` enum + `dueDate` field on `TodoList`, `name` on `User`, and profile/password endpoints. Frontend gets an app-shell with a persistent sidebar, dark mode via Tailwind `class` strategy, redesigned `TodoCard` with priority borders and due-date indicators, and a `/settings` page replacing scattered dialogs.

**Tech Stack:** Symfony 7, Doctrine ORM, PHP 8.3, React 19, TypeScript, Vite, Tailwind CSS, Shadcn/ui, Zustand, TanStack Query, React Router v7, Zod, React Hook Form

---

> **Note:** This plan has two independent phases. Phase 1 (backend) produces working, tested PHP changes. Phase 2 (frontend) depends on Phase 1 being deployed/running.

---

## Phase 1 — Backend

### Task 1: TodoPriority enum

**Files:**
- Create: `backend/src/Enum/TodoPriority.php`

- [ ] **Step 1: Create the enum**

```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum TodoPriority: int
{
    case High   = 1;
    case Medium = 2;
    case Low    = 3;
}
```

Backed as `int` so `ORDER BY t.priority ASC` naturally puts High (1) first.

- [ ] **Step 2: Commit**

```bash
git add backend/src/Enum/TodoPriority.php
git commit -m "feat: add TodoPriority backed int enum"
```

---

### Task 2: Extend TodoList entity (priority + dueDate)

**Files:**
- Modify: `backend/src/Entity/TodoList.php`

- [ ] **Step 1: Add fields after the `$status` column**

In `backend/src/Entity/TodoList.php`, add these two properties after the `$status` property and before `$owner`:

```php
#[ORM\Column(enumType: TodoPriority::class, options: ['default' => 2])]
private TodoPriority $priority = TodoPriority::Medium;

#[ORM\Column(type: 'date_immutable', nullable: true)]
private ?\DateTimeImmutable $dueDate = null;
```

Add `use App\Enum\TodoPriority;` to imports.

- [ ] **Step 2: Add getters and setters** after `setStatus()`:

```php
public function getPriority(): TodoPriority
{
    return $this->priority;
}

public function setPriority(TodoPriority $priority): static
{
    $this->priority = $priority;

    return $this;
}

public function getDueDate(): ?\DateTimeImmutable
{
    return $this->dueDate;
}

public function setDueDate(?\DateTimeImmutable $dueDate): static
{
    $this->dueDate = $dueDate;

    return $this;
}
```

- [ ] **Step 3: Commit**

```bash
git add backend/src/Entity/TodoList.php
git commit -m "feat: add priority and dueDate to TodoList entity"
```

---

### Task 3: Add name field to User entity

**Files:**
- Modify: `backend/src/Entity/User.php`

- [ ] **Step 1: Add `$name` property** after `$email`:

```php
#[ORM\Column(length: 100, nullable: true)]
private ?string $name = null;
```

- [ ] **Step 2: Add getter/setter** after `getEmail()`:

```php
public function getName(): ?string
{
    return $this->name;
}

public function setName(?string $name): static
{
    $this->name = $name;

    return $this;
}
```

- [ ] **Step 3: Commit**

```bash
git add backend/src/Entity/User.php
git commit -m "feat: add nullable name field to User entity"
```

---

### Task 4: Generate and run migration

**Files:**
- Create: `backend/migrations/Version<timestamp>.php` (auto-generated)

- [ ] **Step 1: Generate migration**

```bash
cd backend && ./bin/console doctrine:migrations:diff
```

Expected output: `Generated new migration class to "migrations/Version<timestamp>.php"`

- [ ] **Step 2: Inspect generated SQL** — verify it contains:
  - `ALTER TABLE todo_list ADD priority INT NOT NULL DEFAULT 2`
  - `ALTER TABLE todo_list ADD due_date DATE DEFAULT NULL`
  - `ALTER TABLE "user" ADD name VARCHAR(100) DEFAULT NULL`

- [ ] **Step 3: Run migration on dev DB**

```bash
./bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[notice] Migrating up to Version<timestamp>`

- [ ] **Step 4: Run migration on test DB**

```bash
APP_ENV=test ./bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 5: Commit**

```bash
git add migrations/
git commit -m "feat: migration for priority, dueDate, user name"
```

---

### Task 5: Update TodoRequest DTO

**Files:**
- Modify: `backend/src/DTO/Request/TodoRequest.php`

- [ ] **Step 1: Add priority and dueDate fields** to `TodoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Enum\TodoPriority;
use App\Enum\TodoStatus;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['name'])]
final class TodoRequest
{
    #[OA\Property(type: 'string', maxLength: 255, example: 'My Todo List')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[OA\Property(type: 'string', nullable: true, example: 'A detailed description')]
    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[OA\Property(type: 'string', nullable: true, maxLength: 100, example: 'work')]
    #[Assert\Length(max: 100)]
    public ?string $tag = null;

    #[OA\Property(type: 'string', enum: ['pending', 'in_progress', 'done'], nullable: true, example: 'pending')]
    public ?TodoStatus $status = null;

    #[OA\Property(type: 'string', enum: ['high', 'medium', 'low'], nullable: true, example: 'medium')]
    public ?TodoPriority $priority = null;

    #[OA\Property(type: 'string', format: 'date', nullable: true, example: '2026-05-15')]
    #[Assert\Date]
    public ?string $dueDate = null;
}
```

- [ ] **Step 2: Commit**

```bash
git add backend/src/DTO/Request/TodoRequest.php
git commit -m "feat: add priority and dueDate to TodoRequest DTO"
```

---

### Task 6: Update TodoResponse and AdminTodoResponse DTOs

**Files:**
- Modify: `backend/src/DTO/Response/TodoResponse.php`
- Modify: `backend/src/DTO/Response/AdminTodoResponse.php`

- [ ] **Step 1: Replace `TodoResponse.php` content**:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\TodoList;
use OpenApi\Attributes as OA;

#[OA\Schema]
final readonly class TodoResponse
{
    /**
     * @param TodoItemResponse[] $items
     */
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(type: 'string', example: 'My Todo List')]
        public string $name,
        #[OA\Property(type: 'string', nullable: true, example: 'A description')]
        public ?string $description,
        #[OA\Property(type: 'string', nullable: true, example: 'work')]
        public ?string $tag,
        #[OA\Property(type: 'string', enum: ['pending', 'in_progress', 'done'], example: 'pending')]
        public string $status,
        #[OA\Property(type: 'string', enum: ['high', 'medium', 'low'], example: 'medium')]
        public string $priority,
        #[OA\Property(type: 'string', format: 'date', nullable: true, example: '2026-05-15')]
        public ?string $dueDate,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/TodoItemResponse'))]
        public array $items,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', example: '2024-01-15T12:00:00+00:00')]
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(TodoList $todo): self
    {
        return new self(
            id: $todo->getId(),
            name: $todo->getName(),
            description: $todo->getDescription(),
            tag: $todo->getTag(),
            status: $todo->getStatus()->value,
            priority: match ($todo->getPriority()) {
                \App\Enum\TodoPriority::High   => 'high',
                \App\Enum\TodoPriority::Medium => 'medium',
                \App\Enum\TodoPriority::Low    => 'low',
            },
            dueDate: $todo->getDueDate()?->format('Y-m-d'),
            items: array_map(TodoItemResponse::fromEntity(...), $todo->getTodoItems()->toArray()),
            createdAt: $todo->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $todo->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
```

- [ ] **Step 2: Update `AdminTodoResponse.php`** — add `priority` and `dueDate` constructor params after `status` and update `fromEntity()`:

Add to constructor (after `public string $status`):
```php
#[OA\Property(type: 'string', enum: ['high', 'medium', 'low'], example: 'medium')]
public string $priority,
#[OA\Property(type: 'string', format: 'date', nullable: true, example: '2026-05-15')]
public ?string $dueDate,
```

Add to `fromEntity()` (after `status: $todo->getStatus()->value`):
```php
priority: match ($todo->getPriority()) {
    \App\Enum\TodoPriority::High   => 'high',
    \App\Enum\TodoPriority::Medium => 'medium',
    \App\Enum\TodoPriority::Low    => 'low',
},
dueDate: $todo->getDueDate()?->format('Y-m-d'),
```

- [ ] **Step 3: Commit**

```bash
git add backend/src/DTO/Response/TodoResponse.php backend/src/DTO/Response/AdminTodoResponse.php
git commit -m "feat: add priority and dueDate to Todo response DTOs"
```

---

### Task 7: Update TodoService to persist priority and dueDate

**Files:**
- Modify: `backend/src/Service/TodoService.php`

- [ ] **Step 1: Update `create()` method** — after `if (null !== $dto->status)` block, add:

```php
if (null !== $dto->priority) {
    $todo->setPriority($dto->priority);
}

if (null !== $dto->dueDate) {
    $todo->setDueDate(new \DateTimeImmutable($dto->dueDate));
}
```

- [ ] **Step 2: Update `update()` method** — find the update method and add the same two blocks after setting status:

```php
if (null !== $dto->priority) {
    $todo->setPriority($dto->priority);
}

$todo->setDueDate(null !== $dto->dueDate ? new \DateTimeImmutable($dto->dueDate) : null);
```

Note: `update()` always clears dueDate if not provided (explicit null), unlike `create()`.

- [ ] **Step 3: Update `findAll()` cache key** to include `dueDateFilter`:

Change the `findAll()` signature to:
```php
public function findAll(?string $status, ?string $tag, ?string $search, int $page = 1, int $limit = 10, ?User $owner = null, ?string $dueDateFilter = null): PaginatedTodoResponse
```

Update the cache key:
```php
$cacheKey = sprintf('todos_%s_%s_%s_%s_%s_%s_%s', $owner?->getId(), $status, $tag, $search, $page, $limit, $dueDateFilter);
```

Pass `$dueDateFilter` to repository calls:
```php
$total = $this->repository->countFiltered($status, $tag, $search, $owner, $dueDateFilter);
$items = array_map(
    TodoResponse::fromEntity(...),
    $this->repository->findFiltered($status, $tag, $search, $page, $limit, $owner, $dueDateFilter),
);
```

- [ ] **Step 4: Commit**

```bash
git add backend/src/Service/TodoService.php
git commit -m "feat: persist priority and dueDate in TodoService"
```

---

### Task 8: Update TodoListRepository (sort + dueDate filter)

**Files:**
- Modify: `backend/src/Repository/TodoListRepository.php`

- [ ] **Step 1: Update `findFiltered()` signature and sort** — replace the `orderBy` line:

```php
public function findFiltered(?string $status, ?string $tag, ?string $search, int $page = 1, int $limit = 10, ?User $owner = null, ?string $dueDateFilter = null): array
{
    return $this->buildFilteredQuery($status, $tag, $search, $owner, $dueDateFilter)
        ->leftJoin('t.todoItems', 'ti')
        ->addSelect('ti')
        ->orderBy('t.priority', 'ASC')
        ->addOrderBy('t.createdAt', 'DESC')
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

- [ ] **Step 2: Update `countFiltered()` signature**:

```php
public function countFiltered(?string $status, ?string $tag, ?string $search, ?User $owner = null, ?string $dueDateFilter = null): int
{
    return (int) $this->buildFilteredQuery($status, $tag, $search, $owner, $dueDateFilter)
        ->select('COUNT(t.id)')
        ->getQuery()
        ->getSingleScalarResult();
}
```

- [ ] **Step 3: Update `buildFilteredQuery()` to handle `dueDateFilter`**:

```php
private function buildFilteredQuery(?string $status, ?string $tag, ?string $search, ?User $owner = null, ?string $dueDateFilter = null): QueryBuilder
{
    $qb = $this->createQueryBuilder('t');

    if (null !== $owner) {
        $qb->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner);
    }

    $resolvedStatus = null !== $status ? TodoStatus::tryFrom($status) : null;
    if (null !== $resolvedStatus) {
        $qb->andWhere('t.status = :status')->setParameter('status', $status);
    }

    if (null !== $tag && '' !== $tag) {
        $qb->andWhere('t.tag = :tag')
            ->setParameter('tag', $tag);
    }

    if (null !== $search && '' !== $search) {
        $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
            ->setParameter('search', '%'.$search.'%');
    }

    $today = new \DateTimeImmutable('today');

    match ($dueDateFilter) {
        'overdue'   => $qb->andWhere('t.dueDate IS NOT NULL AND t.dueDate < :today AND t.status != :done')
                          ->setParameter('today', $today)
                          ->setParameter('done', 'done'),
        'today'     => $qb->andWhere('t.dueDate = :today')
                          ->setParameter('today', $today),
        'this_week' => $qb->andWhere('t.dueDate >= :today AND t.dueDate <= :endOfWeek')
                          ->setParameter('today', $today)
                          ->setParameter('endOfWeek', $today->modify('+6 days')),
        default     => null,
    };

    return $qb;
}
```

- [ ] **Step 4: Commit**

```bash
git add backend/src/Repository/TodoListRepository.php
git commit -m "feat: sort todos by priority, add dueDate filter to repository"
```

---

### Task 9: Update TodoController list endpoint

**Files:**
- Modify: `backend/src/Controller/Api/TodoController.php`

- [ ] **Step 1: Add `dueDateFilter` query param doc** — add to the `#[OA\Get]` parameters array on `list()`:

```php
new OA\Parameter(name: 'dueDateFilter', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['overdue', 'today', 'this_week'])),
```

- [ ] **Step 2: Pass `dueDateFilter` to service** — update the `list()` return:

```php
return $this->json($this->todoService->findAll(
    status: $request->query->get('status'),
    tag: $request->query->get('tag'),
    search: $request->query->get('search'),
    page: $page,
    limit: $limit,
    owner: $user,
    dueDateFilter: $request->query->get('dueDateFilter'),
));
```

- [ ] **Step 3: Commit**

```bash
git add backend/src/Controller/Api/TodoController.php
git commit -m "feat: add dueDateFilter query param to todos list endpoint"
```

---

### Task 10: Add profile update and password change endpoints

**Files:**
- Create: `backend/src/DTO/Request/UpdateProfileRequest.php`
- Create: `backend/src/DTO/Request/ChangePasswordRequest.php`
- Modify: `backend/src/Controller/Api/ProfileController.php`

- [ ] **Step 1: Create `UpdateProfileRequest.php`**:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequest
{
    #[Assert\Length(max: 100)]
    public ?string $name = null;
}
```

- [ ] **Step 2: Create `ChangePasswordRequest.php`**:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordRequest
{
    #[Assert\NotBlank]
    public string $currentPassword = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $newPassword = '';
}
```

- [ ] **Step 3: Add `show()` to return full profile** — update `ProfileController::show()`:

```php
#[Route('', name: '_show', methods: ['GET'])]
public function show(): JsonResponse
{
    /** @var User $user */
    $user = $this->getUser();

    return $this->json([
        'id'          => $user->getId(),
        'email'       => $user->getEmail(),
        'name'        => $user->getName(),
        'apiKeyCount' => $user->getApiKeys()->count(),
        'roles'       => $user->getRoles(),
    ]);
}
```

- [ ] **Step 4: Add `updateProfile()` and `changePassword()` methods** to `ProfileController` — inject `UserPasswordHasherInterface` and `EntityManagerInterface` in constructor:

```php
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

public function __construct(
    private readonly ApiKeyService $apiKeyService,
    private readonly UserPasswordHasherInterface $passwordHasher,
    private readonly EntityManagerInterface $em,
) {
}

#[Route('', name: '_update', methods: ['PATCH'])]
public function updateProfile(#[MapRequestPayload] UpdateProfileRequest $dto): JsonResponse
{
    /** @var User $user */
    $user = $this->getUser();
    $user->setName($dto->name);
    $this->em->flush();

    return $this->json([
        'id'    => $user->getId(),
        'email' => $user->getEmail(),
        'name'  => $user->getName(),
    ]);
}

#[Route('/password', name: '_change_password', methods: ['PATCH'])]
public function changePassword(#[MapRequestPayload] ChangePasswordRequest $dto): JsonResponse
{
    /** @var User $user */
    $user = $this->getUser();

    if (!$this->passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
        return $this->json(['error' => 'Current password is incorrect.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $user->setPassword($this->passwordHasher->hashPassword($user, $dto->newPassword));
    $this->em->flush();

    return $this->json(null, Response::HTTP_NO_CONTENT);
}
```

- [ ] **Step 5: Commit**

```bash
git add backend/src/DTO/Request/UpdateProfileRequest.php backend/src/DTO/Request/ChangePasswordRequest.php backend/src/Controller/Api/ProfileController.php
git commit -m "feat: add profile update and password change endpoints"
```

---

### Task 11: Backend tests — priority, dueDate, profile

**Files:**
- Modify: `backend/tests/Controller/Api/TodoControllerTest.php`
- Create: `backend/tests/Controller/Api/ProfileControllerTest.php` (if not exists)

- [ ] **Step 1: Write failing test for priority default in TodoControllerTest** — add to the test class:

```php
public function testCreateTodoDefaultPriorityIsMedium(): void
{
    $this->loginUser($this->createTestUser());
    $this->client->jsonRequest('POST', '/api/todos', ['name' => 'Priority Test']);

    self::assertResponseStatusCodeSame(201);
    $data = json_decode((string) $this->client->getResponse()->getContent(), true);
    self::assertSame('medium', $data['priority']);
    self::assertNull($data['dueDate']);
}

public function testCreateTodoWithHighPriority(): void
{
    $this->loginUser($this->createTestUser());
    $this->client->jsonRequest('POST', '/api/todos', [
        'name'     => 'Urgent task',
        'priority' => 'high',
        'dueDate'  => '2030-12-31',
    ]);

    self::assertResponseStatusCodeSame(201);
    $data = json_decode((string) $this->client->getResponse()->getContent(), true);
    self::assertSame('high', $data['priority']);
    self::assertSame('2030-12-31', $data['dueDate']);
}

public function testTodosOrderedByPriority(): void
{
    $user = $this->createTestUser();
    $this->loginUser($user);

    $this->client->jsonRequest('POST', '/api/todos', ['name' => 'Low task',  'priority' => 'low']);
    $this->client->jsonRequest('POST', '/api/todos', ['name' => 'High task', 'priority' => 'high']);
    $this->client->jsonRequest('POST', '/api/todos', ['name' => 'Med task',  'priority' => 'medium']);

    $this->client->request('GET', '/api/todos');
    $data = json_decode((string) $this->client->getResponse()->getContent(), true);

    self::assertSame('high',   $data['items'][0]['priority']);
    self::assertSame('medium', $data['items'][1]['priority']);
    self::assertSame('low',    $data['items'][2]['priority']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
cd backend && ./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php --filter testCreateTodoDefaultPriorityIsMedium -v
```

Expected: FAIL — `priority` field not present in response.

- [ ] **Step 3: Run all backend tests to verify no regressions**

```bash
./vendor/bin/phpunit
```

All pre-existing tests should still pass (priority field is additive).

- [ ] **Step 4: Run new priority tests — expect pass now**

```bash
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php --filter testCreateTodoDefaultPriorityIsMedium -v
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php --filter testCreateTodoWithHighPriority -v
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php --filter testTodosOrderedByPriority -v
```

Expected: all PASS.

- [ ] **Step 5: Write ProfileController tests** — create `tests/Controller/Api/ProfileControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Enum\UserRole;

final class ProfileControllerTest extends WebTestCase
{
    use \Dama\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function createTestUser(string $email = 'profile@example.com'): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([UserRole::User->value]);
        $user->setPassword('$2y$04$' . str_repeat('a', 53));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }

    public function testShowProfile(): void
    {
        $user = $this->createTestUser();
        $this->loginUser($user);
        $this->client->request('GET', '/api/profile');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame($user->getEmail(), $data['email']);
        self::assertNull($data['name']);
    }

    public function testUpdateProfileName(): void
    {
        $user = $this->createTestUser();
        $this->loginUser($user);
        $this->client->jsonRequest('PATCH', '/api/profile', ['name' => 'John Doe']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('John Doe', $data['name']);
    }

    public function testChangePasswordWithWrongCurrentPassword(): void
    {
        $user = $this->createTestUser();
        $this->loginUser($user);
        $this->client->jsonRequest('PATCH', '/api/profile/password', [
            'currentPassword' => 'wrongpassword',
            'newPassword'     => 'newpassword123',
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
```

- [ ] **Step 6: Run profile tests**

```bash
./vendor/bin/phpunit tests/Controller/Api/ProfileControllerTest.php -v
```

Expected: `testShowProfile` and `testUpdateProfileName` PASS. `testChangePasswordWithWrongCurrentPassword` PASS (returns 422 since the dummy hash won't match "wrongpassword").

- [ ] **Step 7: Run full test suite**

```bash
./vendor/bin/phpunit
```

Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add backend/tests/
git commit -m "test: add priority, dueDate, and profile controller tests"
```

---

## Phase 2 — Frontend

### Task 12: Update TypeScript types

**Files:**
- Modify: `frontend/src/types/todo.ts`

- [ ] **Step 1: Replace `todo.ts` content**:

```typescript
export type TodoStatus   = 'pending' | 'in_progress' | 'done'
export type TodoPriority = 'high' | 'medium' | 'low'
export type DueDateFilter = 'overdue' | 'today' | 'this_week'

export interface TodoItem {
  id: number
  title: string
  isCompleted: boolean
  position: number | null
  createdAt: string
}

export interface CreateTodoItemInput {
  title: string
  position?: number
}

export interface UpdateTodoItemInput {
  title?: string
  isCompleted?: boolean
  position?: number
}

export interface Todo {
  id: number
  name: string
  description: string | null
  tag: string | null
  status: TodoStatus
  priority: TodoPriority
  dueDate: string | null
  items: TodoItem[]
  createdAt: string
  updatedAt: string
}

export interface TodoFilters {
  status?: TodoStatus
  tag?: string
  search?: string
  dueDateFilter?: DueDateFilter
  page?: number
  limit?: number
}

export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  limit: number
  pages: number
}

export interface CreateTodoInput {
  name: string
  description?: string
  tag?: string
  priority?: TodoPriority
  dueDate?: string
}

export interface UpdateTodoInput {
  name: string
  description?: string
  tag?: string
  status?: TodoStatus
  priority?: TodoPriority
  dueDate?: string | null
}
```

- [ ] **Step 2: Run TypeScript check**

```bash
cd frontend && npm run build 2>&1 | head -40
```

Expected: type errors in files that reference `Todo` — these will be resolved as we update each file.

- [ ] **Step 3: Commit**

```bash
git add frontend/src/types/todo.ts
git commit -m "feat: add priority, dueDate, and DueDateFilter types"
```

---

### Task 13: Update useTodos API hook

**Files:**
- Modify: `frontend/src/api/useTodos.ts`

- [ ] **Step 1: Update `useTodos` query function** to pass `dueDateFilter`:

Replace the params block in `useTodos`:
```typescript
const params: Record<string, string> = {}
if (filters.status) params['status'] = filters.status
if (filters.tag) params['tag'] = filters.tag
if (filters.search) params['search'] = filters.search
if (filters.dueDateFilter) params['dueDateFilter'] = filters.dueDateFilter
if (filters.page) params['page'] = String(filters.page)
if (filters.limit) params['limit'] = String(filters.limit)
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/api/useTodos.ts
git commit -m "feat: pass dueDateFilter to todos API"
```

---

### Task 14: Dark mode — Tailwind config + theme store

**Files:**
- Modify: `frontend/tailwind.config.ts`
- Create: `frontend/src/store/themeStore.ts`
- Modify: `frontend/src/main.tsx`
- Modify: `frontend/src/index.css`

- [ ] **Step 1: Enable dark mode class strategy in `tailwind.config.ts`** — add `darkMode: 'class'` at the top level of the config object. The file should look like:

```typescript
import type { Config } from 'tailwindcss'

export default {
  darkMode: 'class',
  content: [
    './index.html',
    './src/**/*.{ts,tsx}',
  ],
  // ... rest of existing config
} satisfies Config
```

- [ ] **Step 2: Create `themeStore.ts`**:

```typescript
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

type Theme = 'light' | 'dark'

interface ThemeState {
  theme: Theme
  toggleTheme: () => void
}

function applyTheme(theme: Theme) {
  if (theme === 'dark') {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

export const useThemeStore = create<ThemeState>()(
  persist(
    (set, get) => ({
      theme: 'light',
      toggleTheme: () => {
        const next: Theme = get().theme === 'light' ? 'dark' : 'light'
        applyTheme(next)
        set({ theme: next })
      },
    }),
    {
      name: 'theme',
      onRehydrateStorage: () => (state) => {
        if (state) applyTheme(state.theme)
      },
    },
  ),
)
```

- [ ] **Step 3: Verify theme applies on page load** — in `frontend/src/main.tsx`, add after imports:

```typescript
import { useThemeStore } from './store/themeStore'

// Apply persisted theme before first render
useThemeStore.getState().theme === 'dark'
  ? document.documentElement.classList.add('dark')
  : document.documentElement.classList.remove('dark')
```

- [ ] **Step 4: Add dark mode CSS variables to `index.css`** — Shadcn/ui uses CSS variables. Add inside `.dark` selector (after existing `:root` block if it exists, otherwise add):

```css
.dark {
  --background: 222.2 84% 4.9%;
  --foreground: 210 40% 98%;
  --card: 222.2 84% 4.9%;
  --card-foreground: 210 40% 98%;
  --popover: 222.2 84% 4.9%;
  --popover-foreground: 210 40% 98%;
  --primary: 210 40% 98%;
  --primary-foreground: 222.2 47.4% 11.2%;
  --secondary: 217.2 32.6% 17.5%;
  --secondary-foreground: 210 40% 98%;
  --muted: 217.2 32.6% 17.5%;
  --muted-foreground: 215 20.2% 65.1%;
  --accent: 217.2 32.6% 17.5%;
  --accent-foreground: 210 40% 98%;
  --destructive: 0 62.8% 30.6%;
  --destructive-foreground: 210 40% 98%;
  --border: 217.2 32.6% 17.5%;
  --input: 217.2 32.6% 17.5%;
  --ring: 212.7 26.8% 83.9%;
}
```

- [ ] **Step 5: Commit**

```bash
git add frontend/tailwind.config.ts frontend/src/store/themeStore.ts frontend/src/main.tsx frontend/src/index.css
git commit -m "feat: add dark mode with Tailwind class strategy and theme store"
```

---

### Task 15: App shell — Sidebar component

**Files:**
- Create: `frontend/src/features/layout/Sidebar.tsx`

- [ ] **Step 1: Create `Sidebar.tsx`**:

```tsx
import { NavLink, useNavigate } from 'react-router-dom'
import { ClipboardList, Settings, Shield, LogOut, Sun, Moon } from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { useThemeStore } from '@/store/themeStore'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const navItems = [
  { to: '/', icon: ClipboardList, label: 'Todos', exact: true },
  { to: '/settings', icon: Settings, label: 'Settings', exact: false },
]

export function Sidebar() {
  const isAdmin   = useAuthStore((s) => s.isAdmin)
  const email     = useAuthStore((s) => s.email)
  const clearToken = useAuthStore((s) => s.clearToken)
  const { theme, toggleTheme } = useThemeStore()
  const navigate  = useNavigate()

  const handleLogout = () => {
    clearToken()
    navigate('/login')
  }

  return (
    <aside className="flex w-56 shrink-0 flex-col border-r bg-card">
      {/* Logo */}
      <div className="flex h-14 items-center gap-2 border-b px-4">
        <ClipboardList className="h-5 w-5 text-primary" />
        <span className="font-semibold tracking-tight">TodoApp</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 space-y-0.5 p-2 pt-3">
        {navItems.map(({ to, icon: Icon, label, exact }) => (
          <NavLink
            key={to}
            to={to}
            end={exact}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                isActive
                  ? 'bg-primary/10 text-primary'
                  : 'text-muted-foreground hover:bg-accent hover:text-foreground',
              )
            }
          >
            <Icon className="h-4 w-4" />
            {label}
          </NavLink>
        ))}

        {isAdmin() && (
          <NavLink
            to="/dashboard"
            className={({ isActive }) =>
              cn(
                'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                isActive
                  ? 'bg-primary/10 text-primary'
                  : 'text-muted-foreground hover:bg-accent hover:text-foreground',
              )
            }
          >
            <Shield className="h-4 w-4" />
            Admin
          </NavLink>
        )}
      </nav>

      {/* Footer */}
      <div className="border-t p-2 space-y-0.5">
        <Button
          variant="ghost"
          size="sm"
          onClick={toggleTheme}
          className="w-full justify-start gap-2.5 text-muted-foreground"
        >
          {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          {theme === 'dark' ? 'Light mode' : 'Dark mode'}
        </Button>

        <div className="truncate px-3 py-1 text-xs text-muted-foreground">{email}</div>

        <Button
          variant="ghost"
          size="sm"
          onClick={handleLogout}
          className="w-full justify-start gap-2.5 text-muted-foreground hover:text-destructive"
        >
          <LogOut className="h-4 w-4" />
          Sign out
        </Button>
      </div>
    </aside>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/layout/Sidebar.tsx
git commit -m "feat: add Sidebar component with nav, theme toggle, and logout"
```

---

### Task 16: App shell — AppLayout component + routing

**Files:**
- Create: `frontend/src/features/layout/AppLayout.tsx`
- Modify: `frontend/src/App.tsx`

- [ ] **Step 1: Create `AppLayout.tsx`**:

```tsx
import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'

export function AppLayout() {
  return (
    <div className="flex h-screen bg-background">
      <Sidebar />
      <main className="flex-1 overflow-y-auto">
        <div className="mx-auto max-w-3xl px-6 py-8">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
```

- [ ] **Step 2: Update `App.tsx`** to use `AppLayout` for protected user routes and add `/settings`:

```tsx
import { Routes, Route } from 'react-router-dom'
import { ProtectedRoute } from './components/ProtectedRoute'
import { AdminRoute } from './components/AdminRoute'
import { AppLayout } from './features/layout/AppLayout'
import { TodoList } from './features/todos/TodoList'
import { SettingsPage } from './features/settings/SettingsPage'
import { LoginPage } from './features/auth/LoginPage'
import { RegisterPage } from './features/auth/RegisterPage'
import { AdminRegisterPage } from './features/auth/AdminRegisterPage'
import { TwoFactorPage } from './features/auth/TwoFactorPage'
import { AdminLayout } from './features/admin/AdminLayout'
import { DashboardOverview } from './features/admin/DashboardOverview'
import { UsersPage } from './features/admin/UsersPage'
import { TodosPage } from './features/admin/TodosPage'
import { ApiKeysPage } from './features/admin/ApiKeysPage'
import { TotpSetupPage } from './features/auth/TotpSetupPage'
import { EnrollPage } from './features/auth/EnrollPage'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/admin/register" element={<AdminRegisterPage />} />
      <Route path="/auth/2fa" element={<TwoFactorPage />} />
      <Route path="/2fa/enroll" element={<EnrollPage />} />

      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route path="/" element={<TodoList />} />
          <Route path="/settings" element={<SettingsPage />} />
        </Route>
      </Route>

      <Route element={<AdminRoute />}>
        <Route path="/dashboard/2fa/setup" element={<TotpSetupPage />} />
        <Route element={<AdminLayout />}>
          <Route path="/dashboard" element={<DashboardOverview />} />
          <Route path="/dashboard/users" element={<UsersPage />} />
          <Route path="/dashboard/todos" element={<TodosPage />} />
          <Route path="/dashboard/api-keys" element={<ApiKeysPage />} />
        </Route>
      </Route>
    </Routes>
  )
}
```

- [ ] **Step 3: Create placeholder `SettingsPage`** (full implementation in Tasks 19–21) — create `frontend/src/features/settings/SettingsPage.tsx`:

```tsx
export function SettingsPage() {
  return <div>Settings — coming soon</div>
}
```

- [ ] **Step 4: Run dev server and verify sidebar renders at `/`**

```bash
cd frontend && npm run dev
```

Open browser at `http://localhost:5173`. Sidebar should appear with "Todos" and "Settings" nav items.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/features/layout/AppLayout.tsx frontend/src/App.tsx frontend/src/features/settings/SettingsPage.tsx
git commit -m "feat: add AppLayout with sidebar and /settings route"
```

---

### Task 17: Simplify TodoList header (remove moved items)

**Files:**
- Modify: `frontend/src/features/todos/TodoList.tsx`

- [ ] **Step 1: Remove logout button and API key button** from the header buttons section. Replace the `<div className="flex items-center gap-2">` in the header with:

```tsx
<div className="flex items-center gap-2">
  <input
    ref={fileInputRef}
    type="file"
    accept=".csv"
    className="hidden"
    onChange={handleImportChange}
  />
  <Button
    variant="outline"
    className="gap-2"
    disabled={importTodos.isPending}
    onClick={() => fileInputRef.current?.click()}
  >
    <Upload className="h-4 w-4" />
    {importTodos.isPending ? 'Importing…' : 'Import CSV'}
  </Button>
  <Button onClick={openCreate} className="gap-2">
    <Plus className="h-4 w-4" />
    New todo
  </Button>
</div>
```

- [ ] **Step 2: Remove unused imports** — remove `LogOut`, `KeyRound` from lucide imports, remove `useNavigate`, `clearToken` from `useAuthStore`, remove `showApiKey` state and `ApiKeyDialog`.

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/todos/TodoList.tsx
git commit -m "refactor: remove logout and api key from TodoList (moved to sidebar/settings)"
```

---

### Task 18: Redesign TodoCard (priority border, dueDate, subtask progress)

**Files:**
- Modify: `frontend/src/features/todos/TodoCard.tsx`

- [ ] **Step 1: Replace `TodoCard.tsx` content**:

```tsx
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Pencil, Trash2, Calendar, AlertCircle } from 'lucide-react'
import { useDeleteTodo, useUpdateTodo } from '@/api/useTodos'
import { useModalStore } from '@/store/modalStore'
import type { Todo, TodoPriority, TodoStatus } from '@/types/todo'
import { toast } from 'sonner'
import { TodoItemList } from './TodoItemList'

const statusVariant: Record<TodoStatus, 'default' | 'secondary' | 'outline'> = {
  pending: 'outline',
  in_progress: 'secondary',
  done: 'default',
}

const statusLabel: Record<TodoStatus, string> = {
  pending: 'Pending',
  in_progress: 'In Progress',
  done: 'Done',
}

const priorityBorderClass: Record<TodoPriority, string> = {
  high:   'border-l-4 border-l-red-500',
  medium: 'border-l-4 border-l-orange-400',
  low:    'border-l-4 border-l-blue-400',
}

const priorityLabel: Record<TodoPriority, string> = {
  high: 'High', medium: 'Medium', low: 'Low',
}

function isOverdue(dueDate: string | null, status: TodoStatus): boolean {
  if (!dueDate || status === 'done') return false
  return new Date(dueDate) < new Date(new Date().toDateString())
}

interface TodoCardProps {
  todo: Todo
}

export function TodoCard({ todo }: TodoCardProps) {
  const updateTodo = useUpdateTodo()
  const deleteTodo = useDeleteTodo()
  const openEdit   = useModalStore((s) => s.openEdit)
  const overdue    = isOverdue(todo.dueDate, todo.status)

  const completedItems = todo.items.filter((i) => i.isCompleted).length
  const totalItems     = todo.items.length
  const progressPct    = totalItems > 0 ? (completedItems / totalItems) * 100 : 0

  const handleCheck = () => {
    const newStatus: TodoStatus = todo.status === 'done' ? 'pending' : 'done'
    updateTodo.mutate(
      {
        id: todo.id,
        name: todo.name,
        description: todo.description ?? undefined,
        tag: todo.tag ?? undefined,
        status: newStatus,
        priority: todo.priority,
        dueDate: todo.dueDate,
      },
      { onSuccess: () => toast.success(newStatus === 'done' ? 'Marked as done!' : 'Marked as pending') },
    )
  }

  const handleDelete = () => {
    deleteTodo.mutate(todo.id, {
      onSuccess: () => toast.success('Todo deleted'),
      onError:   (e) => toast.error((e as Error).message),
    })
  }

  return (
    <Card
      className={`transition-shadow hover:shadow-md ${priorityBorderClass[todo.priority]} ${overdue ? 'bg-destructive/5' : ''} ${todo.status === 'done' ? 'opacity-60' : ''}`}
    >
      <CardContent className="flex items-start gap-4 p-4">
        <Checkbox
          checked={todo.status === 'done'}
          disabled={updateTodo.isPending}
          onCheckedChange={handleCheck}
          className="mt-0.5 shrink-0"
        />
        <div className="min-w-0 flex-1">
          <p className={`font-medium leading-snug ${todo.status === 'done' ? 'text-muted-foreground line-through' : ''}`}>
            {todo.name}
          </p>

          {todo.description && (
            <p className="mt-1 truncate text-sm text-muted-foreground">{todo.description}</p>
          )}

          <div className="mt-2 flex flex-wrap items-center gap-1.5">
            <Badge variant={statusVariant[todo.status]}>{statusLabel[todo.status]}</Badge>
            <Badge variant="outline" className="text-xs">{priorityLabel[todo.priority]}</Badge>
            {todo.tag && <Badge variant="outline">{todo.tag}</Badge>}

            {todo.dueDate && (
              <span className={`flex items-center gap-1 text-xs ${overdue ? 'font-medium text-destructive' : 'text-muted-foreground'}`}>
                {overdue ? <AlertCircle className="h-3 w-3" /> : <Calendar className="h-3 w-3" />}
                {overdue ? 'Overdue · ' : ''}{todo.dueDate}
              </span>
            )}
          </div>

          {totalItems > 0 && (
            <div className="mt-2 space-y-1">
              <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>Subtasks</span>
                <span>{completedItems}/{totalItems}</span>
              </div>
              <div className="h-1.5 w-full rounded-full bg-muted">
                <div
                  className="h-1.5 rounded-full bg-primary transition-all"
                  style={{ width: `${progressPct}%` }}
                />
              </div>
            </div>
          )}

          <TodoItemList todoId={todo.id} items={todo.items} />
        </div>

        <div className="flex shrink-0 gap-1">
          <Button size="icon" variant="ghost" onClick={() => openEdit(todo.id)} className="h-8 w-8">
            <Pencil className="h-3.5 w-3.5" />
          </Button>
          <Button
            size="icon"
            variant="ghost"
            onClick={handleDelete}
            disabled={deleteTodo.isPending}
            className="h-8 w-8 text-destructive hover:text-destructive"
          >
            <Trash2 className="h-3.5 w-3.5" />
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/todos/TodoCard.tsx
git commit -m "feat: redesign TodoCard with priority border, due date, and subtask progress"
```

---

### Task 19: Update TodoFilters (add dueDateFilter)

**Files:**
- Modify: `frontend/src/features/todos/TodoFilters.tsx`
- Modify: `frontend/src/store/todoFilterStore.ts`

- [ ] **Step 1: Update `todoFilterStore.ts`** to handle `dueDateFilter`:

The `TodoFilters` type already includes `dueDateFilter` after Task 12, so no store code change is needed — `setFilter('dueDateFilter', value)` already works via generics.

Verify: `clearFilters` returns `{ page: 1 }` which implicitly clears `dueDateFilter`. No change needed.

- [ ] **Step 2: Replace `TodoFilters.tsx` content**:

```tsx
import { useTodoTags } from '@/api/useTodos'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { useTodoFilterStore } from '@/store/todoFilterStore'
import type { DueDateFilter, TodoStatus } from '@/types/todo'
import { X, Search } from 'lucide-react'

export function TodoFilters() {
  const { filters, setFilter, clearFilters } = useTodoFilterStore()
  const { data: tags = [] } = useTodoTags()
  const hasFilters =
    filters.status !== undefined ||
    filters.tag !== undefined ||
    filters.search !== undefined ||
    filters.dueDateFilter !== undefined

  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="relative min-w-[160px] flex-1 max-w-xs">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search todos..."
          value={filters.search ?? ''}
          onChange={(e) => setFilter('search', e.target.value || undefined)}
          className="pl-8"
        />
      </div>

      <Select
        value={filters.status ?? ''}
        onValueChange={(v) => setFilter('status', (v as TodoStatus) || undefined)}
      >
        <SelectTrigger className="w-36">
          <SelectValue placeholder="All statuses" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="pending">Pending</SelectItem>
          <SelectItem value="in_progress">In Progress</SelectItem>
          <SelectItem value="done">Done</SelectItem>
        </SelectContent>
      </Select>

      <Select
        value={filters.dueDateFilter ?? ''}
        onValueChange={(v) => setFilter('dueDateFilter', (v as DueDateFilter) || undefined)}
      >
        <SelectTrigger className="w-36">
          <SelectValue placeholder="Any due date" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="today">Due today</SelectItem>
          <SelectItem value="this_week">This week</SelectItem>
          <SelectItem value="overdue">Overdue</SelectItem>
        </SelectContent>
      </Select>

      {tags.length > 0 && (
        <Select
          value={filters.tag ?? ''}
          onValueChange={(v) => setFilter('tag', v || undefined)}
        >
          <SelectTrigger className="w-32">
            <SelectValue placeholder="All tags" />
          </SelectTrigger>
          <SelectContent>
            {tags.map((tag) => (
              <SelectItem key={tag} value={tag}>{tag}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      )}

      {hasFilters && (
        <Button variant="ghost" size="sm" onClick={clearFilters} className="gap-1.5 text-muted-foreground">
          <X className="h-3.5 w-3.5" />
          Clear
        </Button>
      )}
    </div>
  )
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/todos/TodoFilters.tsx frontend/src/store/todoFilterStore.ts
git commit -m "feat: add due date filter to TodoFilters"
```

---

### Task 20: Update TodoForm (add priority select + due date)

**Files:**
- Modify: `frontend/src/features/todos/TodoForm.tsx`

- [ ] **Step 1: Read current TodoForm.tsx first** to understand form fields, then add `priority` and `dueDate` fields.

- [ ] **Step 2: Update the Zod schema** in `TodoForm.tsx` to include:

```typescript
const schema = z.object({
  name:        z.string().min(1, 'Name is required').max(255),
  description: z.string().max(65535).optional(),
  tag:         z.string().max(100).optional(),
  status:      z.enum(['pending', 'in_progress', 'done']).optional(),
  priority:    z.enum(['high', 'medium', 'low']).default('medium'),
  dueDate:     z.string().optional(),
})
```

- [ ] **Step 3: Set default values** — in `useForm`, add to `defaultValues`:

```typescript
defaultValues: {
  name:        todo?.name ?? '',
  description: todo?.description ?? '',
  tag:         todo?.tag ?? '',
  status:      todo?.status,
  priority:    todo?.priority ?? 'medium',
  dueDate:     todo?.dueDate ?? '',
}
```

- [ ] **Step 4: Add priority field UI** — add after the tag field (priority applies to both create and edit, unlike status which is edit-only). Use the same `watch/setValue` pattern as the status select:

```tsx
<div className="space-y-1.5">
  <Label>Priority</Label>
  <Select value={watch('priority')} onValueChange={(v) => setValue('priority', v as FormData['priority'])}>
    <SelectTrigger>
      <SelectValue placeholder="Select priority">
        {watch('priority') === 'high' && 'High'}
        {watch('priority') === 'medium' && 'Medium'}
        {watch('priority') === 'low' && 'Low'}
      </SelectValue>
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="high">High</SelectItem>
      <SelectItem value="medium">Medium</SelectItem>
      <SelectItem value="low">Low</SelectItem>
    </SelectContent>
  </Select>
</div>
```

- [ ] **Step 5: Add dueDate field UI** — after the priority field:

```tsx
<div className="space-y-1.5">
  <Label htmlFor="dueDate">Due date</Label>
  <Input
    id="dueDate"
    type="date"
    {...register('dueDate')}
  />
</div>
```

- [ ] **Step 6: Pass priority/dueDate in submit handler** — update the mutation call to include:

```typescript
priority: data.priority,
dueDate:  data.dueDate || undefined,
```

- [ ] **Step 7: Run TypeScript check**

```bash
cd frontend && npm run build 2>&1 | head -40
```

Expected: no type errors.

- [ ] **Step 8: Commit**

```bash
git add frontend/src/features/todos/TodoForm.tsx
git commit -m "feat: add priority and due date fields to TodoForm"
```

---

### Task 21: Extend useApiKey.ts with profile mutations

`frontend/src/api/useApiKey.ts` already has `useProfile()` returning `{ apiKeyCount }` with query key `['profile']`. The backend `GET /api/profile` now returns more fields. Extend the existing file — do NOT create a separate `useProfile.ts` (would conflict on the same query key).

**Files:**
- Modify: `frontend/src/api/useApiKey.ts`

- [ ] **Step 1: Update the `Profile` interface** in `useApiKey.ts` — replace:

```typescript
interface Profile {
  apiKeyCount: number
}
```

with:

```typescript
export interface ProfileData {
  id: number
  email: string
  name: string | null
  apiKeyCount: number
  roles: string[]
}
```

- [ ] **Step 2: Update `useProfile` return type**:

```typescript
export function useProfile() {
  return useQuery({
    queryKey: PROFILE_KEY,
    queryFn: async (): Promise<ProfileData> => {
      const { data } = await api.get<ProfileData>('/api/profile')
      return data
    },
  })
}
```

- [ ] **Step 3: Add `useUpdateProfile` and `useChangePassword` mutations** at the bottom of `useApiKey.ts`:

```typescript
export function useUpdateProfile() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (payload: { name: string | null }): Promise<ProfileData> => {
      const { data } = await api.patch<ProfileData>('/api/profile', payload)
      return data
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: PROFILE_KEY }),
  })
}

export function useChangePassword() {
  return useMutation({
    mutationFn: async (payload: { currentPassword: string; newPassword: string }): Promise<void> => {
      await api.patch('/api/profile/password', payload)
    },
  })
}
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/api/useApiKey.ts
git commit -m "feat: extend useApiKey with full ProfileData, useUpdateProfile, useChangePassword"
```

---

### Task 22: Settings page — Profile section

**Files:**
- Modify: `frontend/src/features/settings/SettingsPage.tsx`
- Create: `frontend/src/features/settings/ProfileSection.tsx`

- [ ] **Step 1: Create `ProfileSection.tsx`**:

```tsx
import { useProfile, useUpdateProfile } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { useEffect } from 'react'

const schema = z.object({ name: z.string().max(100) })
type FormData = z.infer<typeof schema>

export function ProfileSection() {
  const { data: profile, isLoading } = useProfile()
  const updateProfile = useUpdateProfile()

  const { register, handleSubmit, reset, formState: { errors, isDirty } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { name: '' },
  })

  useEffect(() => {
    if (profile) reset({ name: profile.name ?? '' })
  }, [profile, reset])

  const onSubmit = async (data: FormData) => {
    try {
      await updateProfile.mutateAsync({ name: data.name || null })
      toast.success('Profile updated')
    } catch {
      toast.error('Failed to update profile')
    }
  }

  if (isLoading) return <Skeleton className="h-24 w-full" />

  return (
    <div className="space-y-4">
      <div className="space-y-1.5">
        <Label>Email</Label>
        <Input value={profile?.email ?? ''} readOnly className="bg-muted text-muted-foreground" />
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="space-y-1.5">
          <Label htmlFor="name">Display name</Label>
          <Input id="name" placeholder="Your name" {...register('name')} />
          {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
        </div>

        <Button type="submit" disabled={!isDirty || updateProfile.isPending}>
          {updateProfile.isPending ? 'Saving…' : 'Save changes'}
        </Button>
      </form>
    </div>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/settings/ProfileSection.tsx
git commit -m "feat: add ProfileSection to settings"
```

---

### Task 23: Settings page — Security section (password change)

**Files:**
- Create: `frontend/src/features/settings/SecuritySection.tsx`

- [ ] **Step 1: Create `SecuritySection.tsx`**:

```tsx
import { useChangePassword } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'

const schema = z.object({
  currentPassword: z.string().min(1, 'Required'),
  newPassword:     z.string().min(8, 'At least 8 characters'),
  confirmPassword: z.string(),
}).refine((d) => d.newPassword === d.confirmPassword, {
  message: 'Passwords do not match',
  path: ['confirmPassword'],
})

type FormData = z.infer<typeof schema>

export function SecuritySection() {
  const changePassword = useChangePassword()
  const { register, handleSubmit, reset, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
  })

  const onSubmit = async (data: FormData) => {
    try {
      await changePassword.mutateAsync({
        currentPassword: data.currentPassword,
        newPassword:     data.newPassword,
      })
      toast.success('Password changed')
      reset()
    } catch (e) {
      toast.error((e as Error).message ?? 'Failed to change password')
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 max-w-sm">
      <div className="space-y-1.5">
        <Label htmlFor="currentPassword">Current password</Label>
        <Input id="currentPassword" type="password" {...register('currentPassword')} />
        {errors.currentPassword && <p className="text-sm text-destructive">{errors.currentPassword.message}</p>}
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="newPassword">New password</Label>
        <Input id="newPassword" type="password" {...register('newPassword')} />
        {errors.newPassword && <p className="text-sm text-destructive">{errors.newPassword.message}</p>}
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="confirmPassword">Confirm new password</Label>
        <Input id="confirmPassword" type="password" {...register('confirmPassword')} />
        {errors.confirmPassword && <p className="text-sm text-destructive">{errors.confirmPassword.message}</p>}
      </div>

      <Button type="submit" disabled={changePassword.isPending}>
        {changePassword.isPending ? 'Changing…' : 'Change password'}
      </Button>
    </form>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/settings/SecuritySection.tsx
git commit -m "feat: add SecuritySection with password change form"
```

---

### Task 24: Settings page — API Keys section

**Files:**
- Create: `frontend/src/features/settings/ApiKeysSection.tsx`

- [ ] **Step 1: Read `frontend/src/features/todos/ApiKeyDialog.tsx`** to understand the existing API key UI.

- [ ] **Step 2: Create `ApiKeysSection.tsx`** that extracts the content from `ApiKeyDialog` into a standalone section. Copy the core content (key list, create form, revoke button) from `ApiKeyDialog.tsx` and render it without the Dialog wrapper.

The component should:
- Call `useApiKeys` and `useCreateApiKey` hooks from `@/api/useApiKey`
- Render list of existing keys with name, permissions, revoke button
- Render a "Create new key" form inline (not in dialog)

Structure:
```tsx
import { useApiKeys, useCreateApiKey, useRevokeApiKey } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { CopyableKey } from '@/components/CopyableKey'
import { Trash2 } from 'lucide-react'
import { useState } from 'react'
import { toast } from 'sonner'

export function ApiKeysSection() {
  const { data: keys = [] } = useApiKeys()
  const createKey  = useCreateApiKey()
  const revokeKey  = useRevokeApiKey()
  const [name, setName] = useState('')
  const [newKeyValue, setNewKeyValue] = useState<string | null>(null)

  const handleCreate = async () => {
    if (!name.trim()) return
    try {
      const result = await createKey.mutateAsync({ name: name.trim(), permissions: ['read', 'write'] })
      setNewKeyValue(result.keyValue ?? null)
      setName('')
      toast.success('API key created')
    } catch {
      toast.error('Failed to create key')
    }
  }

  const handleRevoke = async (id: number) => {
    try {
      await revokeKey.mutateAsync(id)
      toast.success('Key revoked')
    } catch {
      toast.error('Failed to revoke key')
    }
  }

  return (
    <div className="space-y-6">
      {newKeyValue && (
        <div className="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
          <p className="mb-2 text-sm font-medium text-green-800 dark:text-green-200">Copy your key — shown once only</p>
          <CopyableKey value={newKeyValue} />
        </div>
      )}

      <div className="space-y-2">
        {keys.length === 0 && (
          <p className="text-sm text-muted-foreground">No API keys yet.</p>
        )}
        {keys.map((key) => (
          <div key={key.id} className="flex items-center justify-between rounded-lg border px-3 py-2">
            <div>
              <p className="text-sm font-medium">{key.name}</p>
              <p className="text-xs text-muted-foreground">{key.permissions.join(', ')}</p>
            </div>
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 text-destructive hover:text-destructive"
              onClick={() => handleRevoke(key.id)}
              disabled={revokeKey.isPending}
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        ))}
      </div>

      <div className="flex gap-2">
        <Input
          placeholder="Key name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="max-w-xs"
        />
        <Button onClick={handleCreate} disabled={createKey.isPending || !name.trim()}>
          {createKey.isPending ? 'Creating…' : 'Create key'}
        </Button>
      </div>
    </div>
  )
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/settings/ApiKeysSection.tsx
git commit -m "feat: add ApiKeysSection for settings page"
```

---

### Task 25: Assemble SettingsPage

**Files:**
- Modify: `frontend/src/features/settings/SettingsPage.tsx`

- [ ] **Step 1: Replace placeholder `SettingsPage.tsx` content**:

```tsx
import { ProfileSection } from './ProfileSection'
import { SecuritySection } from './SecuritySection'
import { ApiKeysSection } from './ApiKeysSection'
import { Separator } from '@/components/ui/separator'

function Section({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
  return (
    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
      <div>
        <h3 className="text-sm font-semibold">{title}</h3>
        <p className="mt-1 text-sm text-muted-foreground">{description}</p>
      </div>
      <div className="md:col-span-2">{children}</div>
    </div>
  )
}

export function SettingsPage() {
  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Settings</h1>
        <p className="text-sm text-muted-foreground">Manage your account and preferences.</p>
      </div>

      <Separator />

      <Section title="Profile" description="Your public display name and email.">
        <ProfileSection />
      </Section>

      <Separator />

      <Section title="Security" description="Update your password.">
        <SecuritySection />
      </Section>

      <Separator />

      <Section title="API Keys" description="Create and manage API keys for programmatic access.">
        <ApiKeysSection />
      </Section>
    </div>
  )
}
```

- [ ] **Step 2: Run TypeScript build to verify no errors**

```bash
cd frontend && npm run build 2>&1 | head -60
```

Expected: successful build with no type errors.

- [ ] **Step 3: Start dev server and manually verify**

```bash
npm run dev
```

Check:
- Sidebar renders with Todos / Settings nav
- Dark mode toggle switches theme and persists on reload
- `/settings` shows all three sections
- `/` shows todo list with sidebar

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/settings/SettingsPage.tsx
git commit -m "feat: assemble full SettingsPage with profile, security, and API keys sections"
```

---

### Task 26: Update MSW handlers for new Todo fields

**Files:**
- Modify: `frontend/src/test/mocks/handlers.ts`

- [ ] **Step 1: Read the current handlers file** then add `priority: 'medium'` and `dueDate: null` to every mock Todo object in the handlers.

Each mock todo object should include:
```typescript
priority: 'medium',
dueDate: null,
```

- [ ] **Step 2: Run frontend tests**

```bash
cd frontend && npm run test
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add frontend/src/test/mocks/handlers.ts
git commit -m "test: add priority and dueDate to MSW mock handlers"
```

---

### Task 27: Final verification

- [ ] **Step 1: Run full backend test suite**

```bash
cd backend && ./vendor/bin/phpunit
```

Expected: all tests pass, 0 failures.

- [ ] **Step 2: Run frontend type check**

```bash
cd frontend && npm run build
```

Expected: successful build, no TypeScript errors.

- [ ] **Step 3: Run frontend tests**

```bash
npm run test
```

Expected: all tests pass.

- [ ] **Step 4: Manual smoke test in browser**

- [ ] Create a todo with High priority + due date in the past → card has red left border + overdue indicator
- [ ] Create a todo with Low priority + future due date → card has blue left border + date shown
- [ ] Add 2 subtasks, complete 1 → progress bar shows 1/2 and 50% fill
- [ ] Toggle dark mode → UI switches, persists on page reload
- [ ] Navigate to Settings → all three sections render
- [ ] Update display name → saved successfully
- [ ] Filter by "Overdue" → shows only overdue todos

- [ ] **Step 5: Final commit**

```bash
git add -p  # stage any remaining changes
git commit -m "feat: complete UI/UX redesign — sidebar, dark mode, priority, due dates, settings page"
```
