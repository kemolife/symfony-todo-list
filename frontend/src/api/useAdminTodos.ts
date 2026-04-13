import { useQuery } from '@tanstack/react-query'
import { api } from '../lib/axios'
import type { PaginatedResponse, TodoStatus } from '../types/todo'

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
}

export interface AdminTodoFilters {
  userId?: number
  status?: TodoStatus
  page?: number
  limit?: number
}

const ADMIN_TODOS_KEY = 'admin-todos'

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
