import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/axios'
import type { CreateTodoInput, CreateTodoItemInput, PaginatedResponse, Todo, TodoFilters, TodoItem, UpdateTodoInput, UpdateTodoItemInput } from '../types/todo'

const TODOS_KEY = 'todos'

export function useTodos(filters: TodoFilters = {}) {
  return useQuery({
    queryKey: [TODOS_KEY, filters],
    queryFn: async () => {
      const params: Record<string, string> = {}
      if (filters.status) params['status'] = filters.status
      if (filters.tag) params['tag'] = filters.tag
      if (filters.search) params['search'] = filters.search
      if (filters.dueDateFilter) params['dueDateFilter'] = filters.dueDateFilter
      if (filters.page) params['page'] = String(filters.page)
      if (filters.limit) params['limit'] = String(filters.limit)

      const { data } = await api.get<PaginatedResponse<Todo>>('/api/todos', { params })
      return data
    },
  })
}

export function useTodo(id: number | null) {
  return useQuery({
    queryKey: [TODOS_KEY, id],
    queryFn: async () => {
      const { data } = await api.get<Todo>(`/api/todos/${id}`)
      return data
    },
    enabled: id != null,
  })
}

export function useTodoTags() {
  return useQuery({
    queryKey: [TODOS_KEY, 'tags'],
    queryFn: async () => {
      const { data } = await api.get<string[]>('/api/todos/tags')
      return data
    },
    staleTime: 1000 * 60 * 5,
  })
}

function useInvalidateTodos() {
  const qc = useQueryClient()
  return () => qc.invalidateQueries({ queryKey: [TODOS_KEY] })
}

export function useCreateTodo() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async (input: CreateTodoInput) => {
      const { data } = await api.post<Todo>('/api/todos', input)
      return data
    },
    onSuccess: () => { void invalidateTodos() },
  })
}

export function useUpdateTodo() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async ({ id, ...input }: { id: number } & UpdateTodoInput) => {
      const { data } = await api.put<Todo>(`/api/todos/${id}`, input)
      return data
    },
    onSuccess: () => { void invalidateTodos() },
  })
}

export function useDeleteTodo() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/todos/${id}`)
    },
    onSuccess: () => { void invalidateTodos() },
  })
}

export function useCreateTodoItem() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async ({ todoId, ...input }: { todoId: number } & CreateTodoItemInput) => {
      const { data } = await api.post<TodoItem>(`/api/todos/${todoId}/items`, input)
      return data
    },
    onSuccess: () => { void invalidateTodos() },
  })
}

export function useToggleTodoItem() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async ({ todoId, itemId, ...input }: { todoId: number; itemId: number } & UpdateTodoItemInput) => {
      const { data } = await api.patch<TodoItem>(`/api/todos/${todoId}/items/${itemId}`, input)
      return data
    },
    onSuccess: () => { void invalidateTodos() },
  })
}

export function useDeleteTodoItem() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async ({ todoId, itemId }: { todoId: number; itemId: number }) => {
      await api.delete(`/api/todos/${todoId}/items/${itemId}`)
    },
    onSuccess: () => { void invalidateTodos() },
  })
}

export interface ImportResult {
  created: number
  failed: number
  errors: string[]
}

export interface ColumnMap {
  title: string
  description: string
  tag: string
  status: string
  items: string
}

export function useImportTodos() {
  const invalidateTodos = useInvalidateTodos()
  return useMutation({
    mutationFn: async ({ file, columnMap }: { file: File; columnMap: ColumnMap }) => {
      const form = new FormData()
      form.append('file', file)
      form.append('columnMap', JSON.stringify(columnMap))
      const { data } = await api.post<ImportResult>('/api/todos/import', form, {
        headers: { 'Content-Type': undefined },
      })
      return data
    },
    onSuccess: () => { void invalidateTodos() },
  })
}
