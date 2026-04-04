# Todo App Design — React + Symfony

**Date:** 2026-04-04
**Status:** Approved

## Overview

A fullstack todo list application with a Symfony 7.4 REST API backend and a React frontend. Supports CRUD, filtering by status/tag/search, and tag management. Authentication is out of scope for this phase.

---

## Backend: Symfony 7.4 API

### Entity Changes

Extend the existing `ToDoList` entity:

- Add `status: TodoStatus` (PHP backed enum — `pending`, `in_progress`, `done`)
- Add `updatedAt: DateTimeImmutable` (set on persist and update via lifecycle callbacks)
- Keep: `id`, `name`, `description`, `tag`, `createdAt`

### Project Structure

```
src/
  Controller/Api/     # Thin controllers — delegate to services
  DTO/                # Request and Response DTOs
  Entity/             # Doctrine entities
  Enum/               # TodoStatus backed enum
  EventListener/      # ExceptionListener → consistent JSON error responses
  Repository/         # Query logic (filter by status, tag, search)
  Service/            # TodoService — business logic
```

### API Endpoints

| Method | Path | Description | Response |
|--------|------|-------------|----------|
| GET | `/api/todos` | List all, supports `?status=&tag=&search=` | 200 |
| POST | `/api/todos` | Create todo | 201 |
| GET | `/api/todos/{id}` | Get one | 200 |
| PUT | `/api/todos/{id}` | Update todo | 200 |
| DELETE | `/api/todos/{id}` | Delete todo | 204 |
| GET | `/api/todos/tags` | List all unique tags | 200 |

### Conventions

- Request bodies deserialized into Request DTOs, validated with Symfony Validator
- Responses serialized from Response DTOs (never raw entities)
- Error format: `{"error": "message", "violations": [{"field": "name", "message": "..."}]}`
- HTTP status codes used correctly (201 on create, 204 on delete, 422 on validation failure)
- CORS via NelmioCorsBundle allowing `http://localhost:5173`

### Testing

- PHPUnit + `WebTestCase` (real HTTP requests)
- Separate `app_test` database (same engine as dev)
- `dama/doctrine-test-bundle` for automatic transaction rollback per test
- Fixtures via `doctrine/doctrine-fixtures-bundle`

---

## Frontend: React App

### Tech Stack

- **Vite** — build tool
- **TypeScript** — strict mode
- **TanStack Query v5** — all server state (fetch, cache, mutations)
- **Zustand** — client UI state (active filters, modal open/closed)
- **React Router v7** — routing
- **Tailwind CSS** — styling
- **axios** — HTTP client with base URL + error interceptor
- **react-hook-form + zod** — form handling and validation

### Project Structure

```
src/
  api/           # TanStack Query hooks (useTodos.ts, useTodo.ts, useTodoMutations.ts)
  components/    # Reusable UI (Button, Input, Badge, Modal, Select)
  features/
    todos/
      TodoList.tsx       # List with filter bar
      TodoCard.tsx       # Single todo item
      TodoForm.tsx       # Create/edit form
      TodoFilters.tsx    # Status + tag + search filters
      useTodoFilters.ts  # Zustand-backed filter state hook
  store/         # Zustand stores
  types/         # TypeScript interfaces matching API DTOs
  lib/
    axios.ts       # Configured axios instance
    queryClient.ts # TanStack Query client config
```

### Features

- Todo list with filter bar (filter by `status`, `tag`, free-text `search`)
- Create / edit todo (modal form)
- Status toggle per item (inline)
- Delete with confirmation
- Tag filter populated from `GET /api/todos/tags`
- Optimistic updates on status toggle

### Data Flow

```
React (TanStack Query) ←→ axios ←→ Symfony API ←→ Doctrine ←→ PostgreSQL
```

### Testing

- Vitest + React Testing Library
- Test behavior, not implementation details
- Mock axios at the boundary (`msw` for API mocking)

---

## Skill Artifacts

Two reusable skills created from this design:

- `~/.agents/skills/symfony-api/SKILL.md` — Symfony 7 REST API best practices
- `~/.agents/skills/react-app/SKILL.md` — React + Vite + TS + TanStack Query + Zustand best practices
