# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Symfony 7 REST API backend + React 19 frontend (in `frontend/`) for a Todo application.

## Commands

### Backend

```bash
composer install
./bin/console doctrine:migrations:migrate
./bin/console doctrine:fixtures:load --no-interaction

# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Controller/Api/TodoControllerTest.php

# Start dev server
symfony server:start
# or
php -S localhost:8000 -t public/
```

### Frontend (`cd frontend` first)

```bash
npm install
npm run dev        # Vite dev server on port 5173
npm run build      # TypeScript check + Vite build
npm run lint
npm run test       # Vitest
npm run test -- TodoList.test.tsx  # Single test file
```

### Database

```bash
docker-compose up -d   # Start PostgreSQL 16
./bin/console doctrine:migrations:migrate
APP_ENV=test ./bin/console doctrine:migrations:migrate  # Test DB
```

## Architecture

### Backend (`src/`)

```
Controller/Api/TodoController.php   # HTTP layer only
Service/TodoService.php             # Business logic
Repository/ToDoListRepository.php   # Doctrine queries (pagination, filtering)
Entity/ToDoList.php                 # Doctrine ORM entity with lifecycle callbacks
DTO/Request/TodoRequest.php         # Input validation via Symfony Validator
DTO/Response/TodoResponse.php       # Response shaping
Enum/TodoStatus.php                 # pending | in_progress | done
EventListener/ExceptionListener.php # Centralized error → JSON response
```

The controller delegates to service, service delegates to repository. DTOs handle validation and response shaping — never expose entities directly.

### Frontend (`frontend/src/`)

- **API layer**: `lib/axios.ts` — Axios instance using `VITE_API_URL` (default: `http://localhost:8000`)
- **Data fetching**: TanStack Query for server state (queries + mutations)
- **UI**: shadcn/ui components + Tailwind CSS v4
- **Forms**: React Hook Form + Zod validation
- **Tests**: Vitest + React Testing Library + MSW for API mocking

### API

Base path: `/api/todos`

| Method | Path | Notes |
|--------|------|-------|
| GET | `/api/todos` | Paginated list; query params: `page`, `limit`, `status`, `tag`, `search` |
| GET | `/api/todos/tags` | Unique tag list |
| GET | `/api/todos/{id}` | Single item |
| POST | `/api/todos` | Create |
| PUT | `/api/todos/{id}` | Update |
| DELETE | `/api/todos/{id}` | Delete |

Paginated response shape: `{ items, total, page, limit, pages }`.

### Testing

**Backend**: Integration tests via `WebTestCase`. DAMA Doctrine Test Bundle wraps each test in a rolled-back transaction — no manual DB cleanup needed. Tests hit a real test database configured in `.env.test`.

**Frontend**: MSW handlers in `src/test/mocks/handlers.ts` intercept API calls. No real network requests in tests.

### CORS

Configured in `config/packages/nelmio_cors.yaml`. Allows all methods on `/api/*` from `localhost` / `127.0.0.1`. Override origin pattern via `CORS_ALLOW_ORIGIN` env var.

## Skills

This project has two active skills:
- `symfony-api` — use when working on backend (controllers, entities, DTOs, services, tests)
- `react-app` — use when working on frontend (components, hooks, stores, tests)
