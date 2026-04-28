export type TodoStatus   = 'pending' | 'in_progress' | 'done'
export type TodoPriority = 'high' | 'medium' | 'low'
export type DueDateFilter = 'overdue' | 'today' | 'this_week'

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
  priority: TodoPriority
  dueDate: string | null
  items: TodoItem[]
  createdAt: string
  updatedAt: string
}

export interface TodoFilters {
  status?: TodoStatus
  tag?: string
  search?: string
  dueDateFilter?: DueDateFilter
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
  priority?: TodoPriority
  dueDate?: string
}

export interface UpdateTodoInput {
  name: string
  description?: string
  tag?: string
  status?: TodoStatus
  priority?: TodoPriority
  dueDate?: string | null
}
