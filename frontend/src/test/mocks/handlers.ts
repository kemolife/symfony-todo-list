import { http, HttpResponse } from 'msw'
import type { Todo, TodoItem } from '../../types/todo'

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
  items: [],
  createdAt: '2026-04-04T00:00:00+00:00',
  updatedAt: '2026-04-04T00:00:00+00:00',
}

export const handlers = [
  http.get('http://localhost:8000/api/todos', () =>
    HttpResponse.json({ items: [mockTodo, mockDoneTodo], total: 2, page: 1, limit: 10, pages: 1 }),
  ),
  http.get('http://localhost:8000/api/todos/tags', () =>
    HttpResponse.json(['work']),
  ),
  http.get('http://localhost:8000/api/todos/1', () =>
    HttpResponse.json(mockTodo),
  ),
  http.post('http://localhost:8000/api/todos', () =>
    HttpResponse.json({ ...mockTodo, id: 3, name: 'New todo', items: [] }, { status: 201 }),
  ),
  http.put('http://localhost:8000/api/todos/1', () =>
    HttpResponse.json({ ...mockTodo, name: 'Updated todo', status: 'done' }),
  ),
  http.delete('http://localhost:8000/api/todos/1', () =>
    new HttpResponse(null, { status: 204 }),
  ),
  http.post('http://localhost:8000/api/todos/:todoId/items', () =>
    HttpResponse.json({ ...mockTodoItem, id: 2, title: 'New item' }, { status: 201 }),
  ),
  http.patch('http://localhost:8000/api/todos/:todoId/items/:itemId', () =>
    HttpResponse.json({ ...mockTodoItem, isCompleted: true }),
  ),
  http.delete('http://localhost:8000/api/todos/:todoId/items/:itemId', () =>
    new HttpResponse(null, { status: 204 }),
  ),
]
