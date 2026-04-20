import { useQuery } from '@tanstack/react-query'
import { api } from '../lib/axios'
import type { PaginatedResponse, TodoStatus } from '../types/todo'

export interface AuditLogEntry {
  id: number
  entityType: string
  entityId: number
  entityName: string | null
  action: 'created' | 'updated' | 'deleted'
  changes: Array<{ field: string; from: unknown; to: unknown }> | null
  actorEmail: string
  occurredAt: string
}

export type AdminTodoFilterStatus = TodoStatus | 'deleted'

export interface AdminTodo {
  id: number
  name: string
  description: string | null
  tag: string | null
  status: TodoStatus
  ownerId: number | null
  ownerEmail: string | null
  createdAt: string
  updatedAt: string
  deletedAt: string | null
}

export interface AdminTodoFilters {
  userId?: number
  status?: AdminTodoFilterStatus
  page?: number
  limit?: number
}

const ADMIN_TODOS_KEY = 'admin-todos'

export function useAdminTodoHistory(todoId: number | null) {
  return useQuery({
    queryKey: ['admin-todo-history', todoId],
    queryFn: async () => {
      const { data } = await api.get<AuditLogEntry[]>(`/api/admin/todos/${todoId}/history`)
      return data
    },
    enabled: todoId != null,
  })
}

export function useAdminTodos(filters: AdminTodoFilters = {}) {
  return useQuery({
    queryKey: [ADMIN_TODOS_KEY, filters],
    queryFn: async () => {
      const params: Record<string, string> = {}
      if (filters.userId) params['user_id'] = String(filters.userId)
      if (filters.status) params['status'] = filters.status
      if (filters.page) params['page'] = String(filters.page)
      if (filters.limit) params['limit'] = String(filters.limit)

      const { data } = await api.get<PaginatedResponse<AdminTodo>>('/api/admin/todos', { params })
      return data
    },
  })
}
