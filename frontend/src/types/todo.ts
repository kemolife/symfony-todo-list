export type TodoStatus = 'pending' | 'in_progress' | 'done'

export interface TodoItem {
  id: number
  title: string
  isCompleted: boolean
  position: number | null
  createdAt: string
}

export interface CreateTodoItemInput {
  title: string
  position?: number
}

export interface UpdateTodoItemInput {
  title?: string
  isCompleted?: boolean
  position?: number
}

export interface Todo {
  id: number
  name: string
  description: string | null
  tag: string | null
  status: TodoStatus
  items: TodoItem[]
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
