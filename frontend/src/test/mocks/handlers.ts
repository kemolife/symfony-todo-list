import { http, HttpResponse } from 'msw'
import type { Todo } from '../../types/todo'

export const mockTodo: Todo = {
  id: 1,
  name: 'Test todo',
  description: 'A description',
  tag: 'work',
  status: 'pending',
  createdAt: '2026-04-04T00:00:00+00:00',
  updatedAt: '2026-04-04T00:00:00+00:00',
}

export const mockDoneTodo: Todo = {
  id: 2,
  name: 'Done todo',
  description: null,
  tag: null,
  status: 'done',
  createdAt: '2026-04-04T00:00:00+00:00',
  updatedAt: '2026-04-04T00:00:00+00:00',
}

export const handlers = [
  http.get('http://localhost:8000/api/todos', () =>
    HttpResponse.json([mockTodo, mockDoneTodo]),
  ),
  http.get('http://localhost:8000/api/todos/tags', () =>
    HttpResponse.json(['work']),
  ),
  http.get('http://localhost:8000/api/todos/1', () =>
    HttpResponse.json(mockTodo),
  ),
  http.post('http://localhost:8000/api/todos', () =>
    HttpResponse.json({ ...mockTodo, id: 3, name: 'New todo' }, { status: 201 }),
  ),
  http.put('http://localhost:8000/api/todos/1', () =>
    HttpResponse.json({ ...mockTodo, name: 'Updated todo', status: 'done' }),
  ),
  http.delete('http://localhost:8000/api/todos/1', () =>
    new HttpResponse(null, { status: 204 }),
  ),
]
