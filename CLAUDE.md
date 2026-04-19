# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Symfony 7 REST API backend + React 19 frontend (in `frontend/`) for a Todo application with JWT auth, TOTP 2FA, and role-based access control.

## Commands

### Backend

```bash
composer install
./bin/console doctrine:migrations:migrate
./bin/console doctrine:fixtures:load --no-interaction

./vendor/bin/phpunit                                          # All tests
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php  # Single file

symfony server:start
```

### Messenger worker

```bash
./bin/console messenger:consume async -vv
```

### Frontend (`cd frontend` first)

```bash
npm install
npm run dev        # Vite dev server on port 5173
npm run build      # TypeScript check + Vite build
npm run lint
npm run test       # Vitest
npm run test -- TodoList.test.tsx
```

### Database / Docker

```bash
docker compose up -d                                  # PostgreSQL 16 + RabbitMQ
./bin/console doctrine:migrations:migrate
APP_ENV=test ./bin/console doctrine:migrations:migrate
```

## Environment Files

- `.env` — committed defaults only; never put secrets or local DSNs here
- `.env.local` — local dev overrides (not committed); set `MESSENGER_TRANSPORT_DSN`, `DATABASE_URL`, etc. here
- `.env.test` — test environment overrides

## Backend Architecture

### Request lifecycle

`Controller` → `Service` → `Repository`. Controllers use `#[MapRequestPayload]` to deserialize + validate JSON bodies into request DTOs automatically; invalid bodies return **422** before the controller method runs. Controllers never call `em->persist/flush` — all persistence is in the service layer.

### Authorization

- Class-level `#[IsGranted(UserRole::User->value)]` guards all endpoints
- Resource-level checks use `TodoVoter` via `denyAccessUnlessGranted(TodoVoter::EDIT, $todo)`
- `TodoVoter` compares owner IDs when both entities are persisted; falls back to object identity for transients
- Admin registration (`/api/admin/register`) requires `ADMIN_SECRET` env var verification

### Security / 2FA

- JWT via `lexik/jwt-authentication-bundle`; token payload includes `roles`, `email`, `twoFactorConfirmed`
- `TwoFactorAuthSuccessHandler` intercepts login: if admin has 2FA pending, returns `{ two_factor_required: true, pre_auth_token }` instead of a JWT — the pre-auth token is cached with a 300 s TTL
- `User` implements `TwoFactorInterface` for TOTP; `__serialize()` uses CRC32C hash for session safety

### DTOs

- **Request DTOs** — public properties + Symfony Validator constraints; `validationGroups: ['create']` enables context-specific rules
- **Response DTOs** — `readonly`, created via static `fromEntity()` factories; never mutated
- Two response shapes: `TodoResponse` (user view with nested `TodoItemResponse`) and `AdminTodoResponse` (adds owner email/ID)

### Repository patterns

- `buildFilteredQuery()` / `buildAdminQuery()` are shared between count and find methods to avoid duplication
- `leftJoin` with `addSelect` for eager loading prevents N+1 queries on `TodoItems`
- `TodoStatus::tryFrom($status)` guards enum values before they reach the query builder

### Entity conventions

- `ToDoList` uses `#[ORM\PreUpdate]` lifecycle callback to auto-update `updatedAt`; creation timestamp set in constructor as `\DateTimeImmutable`
- `TodoItem` is ordered `position ASC` via `#[ORM\OrderBy]`; auto-deleted via `cascade + orphanRemoval` when parent is removed
- Doctrine naming strategy: `underscore` — e.g. `todoList` → `todo_list`

### Error handling

`ExceptionListener` catches all exceptions and returns `{ "error": "message" }` JSON. Non-HTTP exceptions map to 500.

### Messenger

Transports: `async` (RabbitMQ via `MESSENGER_TRANSPORT_DSN`), `failed` (Doctrine queue), `sync`. Test env uses `in-memory://`. No `Message`/`MessageHandler` classes exist yet — routing section in `messenger.yaml` is intentionally empty.

### Testing

- `WebTestCase` integration tests; DAMA bundle wraps each test in a rolled-back transaction — write tests assuming a clean state
- Tests create fixtures inline via helper methods (not Foundry)
- `loginUser()` bypasses password verification; dummy hash `'$2y$04$' . str_repeat('a', 53)` is used in test users
- `when@test` in `security.yaml` reduces bcrypt cost for speed
- **Route order matters**: `/api/todos/tags` must be declared before `/api/todos/{id}` in the controller

## Frontend Architecture

### State management (Zustand)

- `useAuthStore` — persisted to `localStorage`; decodes JWT to extract `roles`, `twoFactorConfirmed`, `email`; `isAdmin()` checks for `ROLE_ADMIN`; 2FA setup flag set by `setTokenAndCheckSetup()`
- `useTodoFilterStore` — pagination + filters; resets page to 1 whenever non-page filters change
- `useModalStore` — create/edit modals are mutually exclusive

### API layer

`lib/axios.ts` injects `Authorization: Bearer` from `useAuthStore` on every request. 401/403 clears the token and redirects to `/login`. Error messages extracted from `response.data.error`.

### Routing

- Public: `/login`, `/register`, `/admin/register`, `/auth/2fa`, `/2fa/enroll`
- `<ProtectedRoute>` — requires `isAuthenticated`
- `<AdminRoute>` — requires `isAdmin()`
- Dashboard at `/dashboard/*` with nested admin layout

### API endpoints

Base path: `/api/todos`

| Method | Path | Notes |
|--------|------|-------|
| GET | `/api/todos` | Paginated; params: `page`, `limit`, `status`, `tag`, `search` |
| GET | `/api/todos/tags` | Unique tag list |
| GET | `/api/todos/{id}` | Single item |
| POST | `/api/todos` | Create |
| PUT | `/api/todos/{id}` | Update |
| DELETE | `/api/todos/{id}` | Delete |

Paginated response shape: `{ items, total, page, limit, pages }`.

### Frontend testing

MSW handlers in `src/test/mocks/handlers.ts` intercept all API calls — no real network requests in tests.

## Skills

- `symfony-api` — use when working on backend (controllers, entities, DTOs, services, tests)
- `react-app` — use when working on frontend (components, hooks, stores, tests)
