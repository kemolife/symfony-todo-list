import { http, HttpResponse } from 'msw'
import type { Todo, TodoItem } from '../../types/todo'
import type { ApiKeyEntry } from '../../types/apiKey'

export const mockTodoItem: TodoItem = {
  id: 1,
  title: 'Test item',
  isCompleted: false,
  position: null,
  createdAt: '2026-04-04T00:00:00+00:00',
}

export const mockTodo: Todo = {
  id: 1,
  name: 'Test todo',
  description: 'A description',
  tag: 'work',
  status: 'pending',
  priority: 'medium',
  dueDate: null,
  items: [mockTodoItem],
  createdAt: '2026-04-04T00:00:00+00:00',
  updatedAt: '2026-04-04T00:00:00+00:00',
}

export const mockDoneTodo: Todo = {
  id: 2,
  name: 'Done todo',
  description: null,
  tag: null,
  status: 'done',
  priority: 'medium',
  dueDate: null,
  items: [],
  createdAt: '2026-04-04T00:00:00+00:00',
  updatedAt: '2026-04-04T00:00:00+00:00',
}

export const mockApiKey: ApiKeyEntry = {
  id: 1,
  name: 'Test key',
  description: null,
  permissions: ['read'],
  createdAt: '2026-04-26T17:00:00+00:00',
  lastUsedAt: null,
  prefix: 'a1b2c3d4',
  keyValue: null,
}

export const handlers = [
  http.get('http://localhost:8080/api/profile', () =>
    HttpResponse.json({ id: 1, email: 'test@example.com', name: null, apiKeyCount: 1, roles: ['ROLE_USER'] }),
  ),
  http.patch('http://localhost:8080/api/profile', () =>
    HttpResponse.json({ id: 1, email: 'test@example.com', name: 'Test User', apiKeyCount: 1, roles: ['ROLE_USER'] }),
  ),
  http.patch('http://localhost:8080/api/profile/password', () =>
    new HttpResponse(null, { status: 204 }),
  ),
  http.get('http://localhost:8080/api/profile/api-keys', () =>
    HttpResponse.json([mockApiKey]),
  ),
  http.post('http://localhost:8080/api/profile/api-keys', () =>
    HttpResponse.json(
      { ...mockApiKey, id: 2, keyValue: 'a1b2c3d4'.repeat(8) },
      { status: 201 },
    ),
  ),
  http.delete('http://localhost:8080/api/profile/api-keys/:keyId', () =>
    new HttpResponse(null, { status: 204 }),
  ),
  http.get('http://localhost:8080/api/todos', () =>
    HttpResponse.json({ items: [mockTodo, mockDoneTodo], total: 2, page: 1, limit: 10, pages: 1 }),
  ),
  http.get('http://localhost:8080/api/todos/tags', () =>
    HttpResponse.json(['work']),
  ),
  http.get('http://localhost:8080/api/todos/1', () =>
    HttpResponse.json(mockTodo),
  ),
  http.post('http://localhost:8080/api/todos', () =>
    HttpResponse.json({ ...mockTodo, id: 3, name: 'New todo', items: [] }, { status: 201 }),
  ),
  http.put('http://localhost:8080/api/todos/1', () =>
    HttpResponse.json({ ...mockTodo, name: 'Updated todo', status: 'done' }),
  ),
  http.delete('http://localhost:8080/api/todos/1', () =>
    new HttpResponse(null, { status: 204 }),
  ),
  http.post('http://localhost:8080/api/todos/:todoId/items', () =>
    HttpResponse.json({ ...mockTodoItem, id: 2, title: 'New item' }, { status: 201 }),
  ),
  http.patch('http://localhost:8080/api/todos/:todoId/items/:itemId', () =>
    HttpResponse.json({ ...mockTodoItem, isCompleted: true }),
  ),
  http.delete('http://localhost:8080/api/todos/:todoId/items/:itemId', () =>
    new HttpResponse(null, { status: 204 }),
  ),
]
