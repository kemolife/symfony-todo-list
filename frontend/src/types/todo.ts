export type TodoStatus = 'pending' | 'in_progress' | 'done'

export interface Todo {
  id: number
  name: string
  description: string | null
  tag: string | null
  status: TodoStatus
  createdAt: string
  updatedAt: string
}

export interface TodoFilters {
  status?: TodoStatus
  tag?: string
  search?: string
  page?: number
  limit?: number
}

export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  limit: number
  pages: number
}

export interface CreateTodoInput {
  name: string
  description?: string
  tag?: string
}

export interface UpdateTodoInput {
  name: string
  description?: string
  tag?: string
  status?: TodoStatus
}
