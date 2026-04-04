import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/axios'
import type { CreateTodoInput, Todo, TodoFilters, UpdateTodoInput } from '../types/todo'

const TODOS_KEY = 'todos'

export function useTodos(filters: TodoFilters = {}) {
  return useQuery({
    queryKey: [TODOS_KEY, filters],
    queryFn: async () => {
      const params: Record<string, string> = {}
      if (filters.status) params['status'] = filters.status
      if (filters.tag) params['tag'] = filters.tag
      if (filters.search) params['search'] = filters.search

      const { data } = await api.get<Todo[]>('/api/todos', { params })
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

export function useCreateTodo() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (input: CreateTodoInput) => {
      const { data } = await api.post<Todo>('/api/todos', input)
      return data
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [TODOS_KEY] })
    },
  })
}

export function useUpdateTodo() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, ...input }: { id: number } & UpdateTodoInput) => {
      const { data } = await api.put<Todo>(`/api/todos/${id}`, input)
      return data
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [TODOS_KEY] })
    },
  })
}

export function useDeleteTodo() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/todos/${id}`)
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [TODOS_KEY] })
    },
  })
}
