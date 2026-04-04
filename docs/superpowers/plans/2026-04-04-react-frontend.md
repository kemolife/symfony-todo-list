# React Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a React frontend for the todo API — list, create, update (including status), delete, and filter todos by status, tag, and search text.

**Architecture:** Feature-based folder structure under `frontend/src/features/todos/`. TanStack Query owns all server state. Zustand owns client UI state (filters, modal open/closed). Components are dumb — logic lives in hooks. Forms use react-hook-form + zod.

**Tech Stack:** Vite, TypeScript (strict), React 19, TanStack Query v5, Zustand, React Router v7, Tailwind CSS v4, axios, react-hook-form, zod, Vitest, React Testing Library, MSW

**Prerequisite:** Symfony API from `2026-04-04-symfony-api.md` must be running on `http://localhost:8000`.

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `frontend/` | Vite project root |
| Create | `frontend/src/lib/axios.ts` | Configured axios instance |
| Create | `frontend/src/lib/queryClient.ts` | TanStack Query client config |
| Create | `frontend/src/types/todo.ts` | TypeScript interfaces matching API DTOs |
| Create | `frontend/src/api/useTodos.ts` | All TanStack Query hooks for todos |
| Create | `frontend/src/store/todoFilterStore.ts` | Zustand filter state |
| Create | `frontend/src/store/modalStore.ts` | Zustand modal open/close state |
| Create | `frontend/src/components/Badge.tsx` | Status/tag badge UI |
| Create | `frontend/src/components/Button.tsx` | Reusable button |
| Create | `frontend/src/components/Input.tsx` | Reusable text input |
| Create | `frontend/src/components/Modal.tsx` | Modal wrapper |
| Create | `frontend/src/components/Select.tsx` | Reusable select dropdown |
| Create | `frontend/src/features/todos/TodoCard.tsx` | Single todo row with status toggle + delete |
| Create | `frontend/src/features/todos/TodoFilters.tsx` | Filter bar (status, tag, search) |
| Create | `frontend/src/features/todos/TodoForm.tsx` | Create/edit form |
| Create | `frontend/src/features/todos/TodoList.tsx` | Full list with filters + create button |
| Modify | `frontend/src/App.tsx` | Root layout with router |
| Modify | `frontend/src/main.tsx` | Providers setup |
| Create | `frontend/src/test/setup.ts` | Vitest + Testing Library setup |
| Create | `frontend/src/test/mocks/handlers.ts` | MSW request handlers |
| Create | `frontend/src/test/mocks/server.ts` | MSW server |
| Create | `frontend/src/features/todos/__tests__/TodoList.test.tsx` | TodoList behavior tests |
| Create | `frontend/src/features/todos/__tests__/TodoForm.test.tsx` | TodoForm behavior tests |

---

## Task 1: Scaffold Project + Install Dependencies

**Files:**
- Create: `frontend/` (entire Vite project)

- [ ] **Step 1: Scaffold Vite project**

From the repo root (`symfony-learn-7/`):

```bash
npm create vite@latest frontend -- --template react-ts
cd frontend
```

- [ ] **Step 2: Install runtime dependencies**

```bash
npm install \
  @tanstack/react-query \
  axios \
  zustand \
  react-router-dom \
  react-hook-form \
  zod \
  @hookform/resolvers
```

- [ ] **Step 3: Install dev dependencies**

```bash
npm install -D \
  vitest \
  @testing-library/react \
  @testing-library/jest-dom \
  @testing-library/user-event \
  msw \
  jsdom \
  tailwindcss \
  @tailwindcss/vite
```

- [ ] **Step 4: Configure Tailwind CSS v4**

Replace `frontend/vite.config.ts` with:

```typescript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 5173,
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
  },
})
```

- [ ] **Step 5: Replace src/index.css content**

```css
@import "tailwindcss";
```

- [ ] **Step 6: Update tsconfig.json for strict mode**

Replace `frontend/tsconfig.json` with:

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "isolatedModules": true,
    "moduleDetection": "force",
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUncheckedIndexedAccess": true,
    "noFallthroughCasesInSwitch": true
  },
  "include": ["src"]
}
```

- [ ] **Step 7: Add .env file for API URL**

Create `frontend/.env`:

```
VITE_API_URL=http://localhost:8000
```

- [ ] **Step 8: Verify dev server starts**

```bash
npm run dev
```

Expected: `Local: http://localhost:5173/` in terminal. Stop with Ctrl+C.

- [ ] **Step 9: Commit**

```bash
cd ..  # back to repo root
git add frontend/
git commit -m "feat: scaffold React frontend with Vite + TypeScript + Tailwind"
```

---

## Task 2: HTTP Client + Query Client

**Files:**
- Create: `frontend/src/lib/axios.ts`
- Create: `frontend/src/lib/queryClient.ts`

- [ ] **Step 1: Create axios instance**

Create `frontend/src/lib/axios.ts`:

```typescript
import axios from 'axios'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000',
  headers: { 'Content-Type': 'application/json' },
})

api.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error)) {
      const message = (error.response?.data as { error?: string } | undefined)?.error
        ?? error.message
        ?? 'Unknown error'
      return Promise.reject(new Error(message))
    }
    return Promise.reject(error)
  },
)
```

- [ ] **Step 2: Create query client**

Create `frontend/src/lib/queryClient.ts`:

```typescript
import { QueryClient } from '@tanstack/react-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60,
      retry: 1,
    },
  },
})
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/lib/
git commit -m "feat: add axios instance and TanStack Query client"
```

---

## Task 3: TypeScript Types

**Files:**
- Create: `frontend/src/types/todo.ts`

- [ ] **Step 1: Create types**

Create `frontend/src/types/todo.ts`:

```typescript
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
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/types/
git commit -m "feat: add TypeScript types matching API DTOs"
```

---

## Task 4: API Hooks (TanStack Query)

**Files:**
- Create: `frontend/src/api/useTodos.ts`

- [ ] **Step 1: Create hooks file**

Create `frontend/src/api/useTodos.ts`:

```typescript
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
      qc.invalidateQueries({ queryKey: [TODOS_KEY] })
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
      qc.invalidateQueries({ queryKey: [TODOS_KEY] })
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
      qc.invalidateQueries({ queryKey: [TODOS_KEY] })
    },
  })
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/api/
git commit -m "feat: add TanStack Query hooks for todos API"
```

---

## Task 5: Zustand Stores

**Files:**
- Create: `frontend/src/store/todoFilterStore.ts`
- Create: `frontend/src/store/modalStore.ts`

- [ ] **Step 1: Create filter store**

Create `frontend/src/store/todoFilterStore.ts`:

```typescript
import { create } from 'zustand'
import type { TodoFilters } from '../types/todo'

interface TodoFilterState {
  filters: TodoFilters
  setFilter: <K extends keyof TodoFilters>(key: K, value: TodoFilters[K]) => void
  clearFilters: () => void
}

export const useTodoFilterStore = create<TodoFilterState>((set) => ({
  filters: {},
  setFilter: (key, value) =>
    set((state) => ({ filters: { ...state.filters, [key]: value } })),
  clearFilters: () => set({ filters: {} }),
}))
```

- [ ] **Step 2: Create modal store**

Create `frontend/src/store/modalStore.ts`:

```typescript
import { create } from 'zustand'

interface ModalState {
  editingTodoId: number | null
  isCreateOpen: boolean
  openCreate: () => void
  openEdit: (id: number) => void
  close: () => void
}

export const useModalStore = create<ModalState>((set) => ({
  editingTodoId: null,
  isCreateOpen: false,
  openCreate: () => set({ isCreateOpen: true, editingTodoId: null }),
  openEdit: (id) => set({ editingTodoId: id, isCreateOpen: false }),
  close: () => set({ isCreateOpen: false, editingTodoId: null }),
}))
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/store/
git commit -m "feat: add Zustand stores for filters and modal state"
```

---

## Task 6: Reusable Components

**Files:**
- Create: `frontend/src/components/Badge.tsx`
- Create: `frontend/src/components/Button.tsx`
- Create: `frontend/src/components/Input.tsx`
- Create: `frontend/src/components/Modal.tsx`
- Create: `frontend/src/components/Select.tsx`

- [ ] **Step 1: Create Badge**

Create `frontend/src/components/Badge.tsx`:

```typescript
interface BadgeProps {
  label: string
  variant?: 'default' | 'success' | 'warning' | 'info'
}

const variants = {
  default: 'bg-gray-100 text-gray-700',
  success: 'bg-green-100 text-green-800',
  warning: 'bg-yellow-100 text-yellow-800',
  info: 'bg-blue-100 text-blue-800',
}

export function Badge({ label, variant = 'default' }: BadgeProps) {
  return (
    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${variants[variant]}`}>
      {label}
    </span>
  )
}
```

- [ ] **Step 2: Create Button**

Create `frontend/src/components/Button.tsx`:

```typescript
import type { ButtonHTMLAttributes } from 'react'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger'
  size?: 'sm' | 'md'
}

const variants = {
  primary: 'bg-blue-600 text-white hover:bg-blue-700',
  secondary: 'bg-gray-100 text-gray-700 hover:bg-gray-200',
  danger: 'bg-red-600 text-white hover:bg-red-700',
}

const sizes = {
  sm: 'px-2 py-1 text-sm',
  md: 'px-4 py-2 text-sm',
}

export function Button({ variant = 'primary', size = 'md', className = '', ...props }: ButtonProps) {
  return (
    <button
      {...props}
      className={`rounded font-medium transition-colors disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${className}`}
    />
  )
}
```

- [ ] **Step 3: Create Input**

Create `frontend/src/components/Input.tsx`:

```typescript
import type { InputHTMLAttributes } from 'react'
import { forwardRef } from 'react'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: string
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ error, className = '', ...props }, ref) => (
    <div className="w-full">
      <input
        ref={ref}
        {...props}
        className={`w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
          error ? 'border-red-500' : 'border-gray-300'
        } ${className}`}
      />
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  ),
)
Input.displayName = 'Input'
```

- [ ] **Step 4: Create Select**

Create `frontend/src/components/Select.tsx`:

```typescript
import type { SelectHTMLAttributes } from 'react'
import { forwardRef } from 'react'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  placeholder?: string
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ placeholder, children, className = '', ...props }, ref) => (
    <select
      ref={ref}
      {...props}
      className={`rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${className}`}
    >
      {placeholder && <option value="">{placeholder}</option>}
      {children}
    </select>
  ),
)
Select.displayName = 'Select'
```

- [ ] **Step 5: Create Modal**

Create `frontend/src/components/Modal.tsx`:

```typescript
import type { ReactNode } from 'react'
import { useEffect } from 'react'

interface ModalProps {
  open: boolean
  onClose: () => void
  title: string
  children: ReactNode
}

export function Modal({ open, onClose, title, children }: ModalProps) {
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose()
    }
    if (open) document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative z-10 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold">{title}</h2>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        {children}
      </div>
    </div>
  )
}
```

- [ ] **Step 6: Commit**

```bash
git add frontend/src/components/
git commit -m "feat: add reusable UI components (Badge, Button, Input, Select, Modal)"
```

---

## Task 7: TodoCard Component

**Files:**
- Create: `frontend/src/features/todos/TodoCard.tsx`

- [ ] **Step 1: Create TodoCard**

Create `frontend/src/features/todos/TodoCard.tsx`:

```typescript
import { Badge } from '../../components/Badge'
import { Button } from '../../components/Button'
import { useDeleteTodo, useUpdateTodo } from '../../api/useTodos'
import { useModalStore } from '../../store/modalStore'
import type { Todo, TodoStatus } from '../../types/todo'

const statusVariant: Record<TodoStatus, 'default' | 'info' | 'success'> = {
  pending: 'default',
  in_progress: 'info',
  done: 'success',
}

const statusLabel: Record<TodoStatus, string> = {
  pending: 'Pending',
  in_progress: 'In Progress',
  done: 'Done',
}

const nextStatus: Record<TodoStatus, TodoStatus> = {
  pending: 'in_progress',
  in_progress: 'done',
  done: 'pending',
}

interface TodoCardProps {
  todo: Todo
}

export function TodoCard({ todo }: TodoCardProps) {
  const updateTodo = useUpdateTodo()
  const deleteTodo = useDeleteTodo()
  const openEdit = useModalStore((s) => s.openEdit)

  const handleStatusToggle = () => {
    updateTodo.mutate({
      id: todo.id,
      name: todo.name,
      description: todo.description ?? undefined,
      tag: todo.tag ?? undefined,
      status: nextStatus[todo.status],
    })
  }

  const handleDelete = () => {
    if (confirm(`Delete "${todo.name}"?`)) {
      deleteTodo.mutate(todo.id)
    }
  }

  return (
    <div className="flex items-start justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
      <div className="flex-1 min-w-0 mr-4">
        <p className={`font-medium ${todo.status === 'done' ? 'line-through text-gray-400' : ''}`}>
          {todo.name}
        </p>
        {todo.description && (
          <p className="mt-1 text-sm text-gray-500 truncate">{todo.description}</p>
        )}
        <div className="mt-2 flex gap-2">
          <Badge label={statusLabel[todo.status]} variant={statusVariant[todo.status]} />
          {todo.tag && <Badge label={todo.tag} />}
        </div>
      </div>
      <div className="flex shrink-0 gap-2">
        <Button size="sm" variant="secondary" onClick={handleStatusToggle} disabled={updateTodo.isPending}>
          →
        </Button>
        <Button size="sm" variant="secondary" onClick={() => openEdit(todo.id)}>
          Edit
        </Button>
        <Button size="sm" variant="danger" onClick={handleDelete} disabled={deleteTodo.isPending}>
          Del
        </Button>
      </div>
    </div>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/
git commit -m "feat: add TodoCard with status toggle and delete"
```

---

## Task 8: TodoFilters Component

**Files:**
- Create: `frontend/src/features/todos/TodoFilters.tsx`

- [ ] **Step 1: Create filters component**

Create `frontend/src/features/todos/TodoFilters.tsx`:

```typescript
import { useTodoTags } from '../../api/useTodos'
import { Input } from '../../components/Input'
import { Select } from '../../components/Select'
import { Button } from '../../components/Button'
import { useTodoFilterStore } from '../../store/todoFilterStore'
import type { TodoStatus } from '../../types/todo'

export function TodoFilters() {
  const { filters, setFilter, clearFilters } = useTodoFilterStore()
  const { data: tags = [] } = useTodoTags()

  return (
    <div className="flex flex-wrap gap-3">
      <Input
        placeholder="Search..."
        value={filters.search ?? ''}
        onChange={(e) => setFilter('search', e.target.value || undefined)}
        className="max-w-xs"
      />

      <Select
        value={filters.status ?? ''}
        onChange={(e) => setFilter('status', (e.target.value as TodoStatus) || undefined)}
        placeholder="All statuses"
      >
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="done">Done</option>
      </Select>

      <Select
        value={filters.tag ?? ''}
        onChange={(e) => setFilter('tag', e.target.value || undefined)}
        placeholder="All tags"
      >
        {tags.map((tag) => (
          <option key={tag} value={tag}>{tag}</option>
        ))}
      </Select>

      {(filters.status || filters.tag || filters.search) && (
        <Button variant="secondary" size="sm" onClick={clearFilters}>
          Clear filters
        </Button>
      )}
    </div>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/todos/TodoFilters.tsx
git commit -m "feat: add TodoFilters component"
```

---

## Task 9: TodoForm Component

**Files:**
- Create: `frontend/src/features/todos/TodoForm.tsx`

- [ ] **Step 1: Create form component**

Create `frontend/src/features/todos/TodoForm.tsx`:

```typescript
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateTodo, useTodo, useUpdateTodo } from '../../api/useTodos'
import { Button } from '../../components/Button'
import { Input } from '../../components/Input'
import { Select } from '../../components/Select'

const schema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  description: z.string().max(65535).optional(),
  tag: z.string().max(100).optional(),
  status: z.enum(['pending', 'in_progress', 'done']).optional(),
})

type FormData = z.infer<typeof schema>

interface TodoFormProps {
  todoId?: number | null
  onSuccess: () => void
}

export function TodoForm({ todoId, onSuccess }: TodoFormProps) {
  const isEdit = todoId != null
  const { data: existing } = useTodo(todoId ?? null)
  const createTodo = useCreateTodo()
  const updateTodo = useUpdateTodo()

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({ resolver: zodResolver(schema) })

  useEffect(() => {
    if (isEdit && existing) {
      reset({
        name: existing.name,
        description: existing.description ?? '',
        tag: existing.tag ?? '',
        status: existing.status,
      })
    }
  }, [existing, isEdit, reset])

  const onSubmit = async (data: FormData) => {
    if (isEdit && todoId != null) {
      await updateTodo.mutateAsync({ id: todoId, ...data })
    } else {
      await createTodo.mutateAsync(data)
    }
    onSuccess()
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <Input
        {...register('name')}
        placeholder="Todo name *"
        error={errors.name?.message}
      />
      <Input
        {...register('description')}
        placeholder="Description (optional)"
        error={errors.description?.message}
      />
      <Input
        {...register('tag')}
        placeholder="Tag (optional)"
        error={errors.tag?.message}
      />
      {isEdit && (
        <Select {...register('status')}>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="done">Done</option>
        </Select>
      )}
      <div className="flex justify-end gap-2">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving...' : isEdit ? 'Update' : 'Create'}
        </Button>
      </div>
    </form>
  )
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/todos/TodoForm.tsx
git commit -m "feat: add TodoForm with react-hook-form + zod validation"
```

---

## Task 10: TodoList + App Wiring

**Files:**
- Create: `frontend/src/features/todos/TodoList.tsx`
- Modify: `frontend/src/App.tsx`
- Modify: `frontend/src/main.tsx`

- [ ] **Step 1: Create TodoList**

Create `frontend/src/features/todos/TodoList.tsx`:

```typescript
import { useTodos } from '../../api/useTodos'
import { useModalStore } from '../../store/modalStore'
import { useTodoFilterStore } from '../../store/todoFilterStore'
import { Button } from '../../components/Button'
import { Modal } from '../../components/Modal'
import { TodoCard } from './TodoCard'
import { TodoFilters } from './TodoFilters'
import { TodoForm } from './TodoForm'

export function TodoList() {
  const filters = useTodoFilterStore((s) => s.filters)
  const { data: todos, isLoading, error } = useTodos(filters)
  const { isCreateOpen, editingTodoId, openCreate, close } = useModalStore()

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Todos</h1>
        <Button onClick={openCreate}>+ New Todo</Button>
      </div>

      <TodoFilters />

      {isLoading && <p className="text-gray-500">Loading...</p>}
      {error && <p className="text-red-600">{error.message}</p>}

      {todos?.length === 0 && !isLoading && (
        <p className="text-center text-gray-400 py-8">No todos found.</p>
      )}

      <div className="space-y-3">
        {todos?.map((todo) => <TodoCard key={todo.id} todo={todo} />)}
      </div>

      <Modal open={isCreateOpen} onClose={close} title="New Todo">
        <TodoForm onSuccess={close} />
      </Modal>

      <Modal open={editingTodoId != null} onClose={close} title="Edit Todo">
        <TodoForm todoId={editingTodoId} onSuccess={close} />
      </Modal>
    </div>
  )
}
```

- [ ] **Step 2: Update main.tsx**

Replace `frontend/src/main.tsx`:

```typescript
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'
import { queryClient } from './lib/queryClient'
import App from './App'
import './index.css'

const root = document.getElementById('root')
if (!root) throw new Error('Root element not found')

createRoot(root).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <App />
      </BrowserRouter>
    </QueryClientProvider>
  </StrictMode>,
)
```

- [ ] **Step 3: Update App.tsx**

Replace `frontend/src/App.tsx`:

```typescript
import { Routes, Route } from 'react-router-dom'
import { TodoList } from './features/todos/TodoList'

export default function App() {
  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      <Routes>
        <Route path="/" element={<TodoList />} />
      </Routes>
    </div>
  )
}
```

- [ ] **Step 4: Delete Vite boilerplate**

```bash
rm frontend/src/App.css frontend/src/assets/react.svg 2>/dev/null; true
```

- [ ] **Step 5: Run dev server and verify UI**

```bash
cd frontend && npm run dev
```

Open `http://localhost:5173`. You should see the Todo list. Make sure the Symfony server is also running (`symfony server:start -d` from the backend).

Expected: Page loads, "No todos found" message shows, "+ New Todo" button opens a modal.

- [ ] **Step 6: Commit**

```bash
cd ..
git add frontend/src/
git commit -m "feat: wire up TodoList, App, and providers"
```

---

## Task 11: Tests Setup + Tests

**Files:**
- Create: `frontend/src/test/setup.ts`
- Create: `frontend/src/test/mocks/handlers.ts`
- Create: `frontend/src/test/mocks/server.ts`
- Create: `frontend/src/features/todos/__tests__/TodoList.test.tsx`
- Create: `frontend/src/features/todos/__tests__/TodoForm.test.tsx`

- [ ] **Step 1: Create test setup**

Create `frontend/src/test/setup.ts`:

```typescript
import '@testing-library/jest-dom'
import { afterAll, afterEach, beforeAll } from 'vitest'
import { server } from './mocks/server'

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }))
afterEach(() => server.resetHandlers())
afterAll(() => server.close())
```

- [ ] **Step 2: Create MSW handlers**

Create `frontend/src/test/mocks/handlers.ts`:

```typescript
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
```

- [ ] **Step 3: Create MSW server**

Create `frontend/src/test/mocks/server.ts`:

```typescript
import { setupServer } from 'msw/node'
import { handlers } from './handlers'

export const server = setupServer(...handlers)
```

- [ ] **Step 4: Write failing TodoList tests**

Create `frontend/src/features/todos/__tests__/TodoList.test.tsx`:

```typescript
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'
import { describe, it, expect, beforeEach } from 'vitest'
import { TodoList } from '../TodoList'

function renderTodoList() {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(
    <QueryClientProvider client={client}>
      <BrowserRouter>
        <TodoList />
      </BrowserRouter>
    </QueryClientProvider>,
  )
}

describe('TodoList', () => {
  it('shows todos from API', async () => {
    renderTodoList()
    expect(await screen.findByText('Test todo')).toBeInTheDocument()
    expect(screen.getByText('Done todo')).toBeInTheDocument()
  })

  it('shows loading state initially', () => {
    renderTodoList()
    expect(screen.getByText('Loading...')).toBeInTheDocument()
  })

  it('opens create modal when New Todo is clicked', async () => {
    const user = userEvent.setup()
    renderTodoList()

    await screen.findByText('Test todo')
    await user.click(screen.getByText('+ New Todo'))

    expect(screen.getByText('New Todo')).toBeInTheDocument()
  })

  it('shows done status badge', async () => {
    renderTodoList()
    await screen.findByText('Done todo')
    expect(screen.getByText('Done')).toBeInTheDocument()
  })
})
```

- [ ] **Step 5: Write failing TodoForm tests**

Create `frontend/src/features/todos/__tests__/TodoForm.test.tsx`:

```typescript
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { describe, it, expect, vi } from 'vitest'
import { TodoForm } from '../TodoForm'

function renderForm(props: { todoId?: number | null; onSuccess?: () => void }) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(
    <QueryClientProvider client={client}>
      <TodoForm todoId={props.todoId ?? null} onSuccess={props.onSuccess ?? vi.fn()} />
    </QueryClientProvider>,
  )
}

describe('TodoForm', () => {
  it('shows validation error when name is empty', async () => {
    const user = userEvent.setup()
    renderForm({})

    await user.click(screen.getByRole('button', { name: /create/i }))

    expect(await screen.findByText('Name is required')).toBeInTheDocument()
  })

  it('calls onSuccess after successful create', async () => {
    const user = userEvent.setup()
    const onSuccess = vi.fn()
    renderForm({ onSuccess })

    await user.type(screen.getByPlaceholderText('Todo name *'), 'New todo')
    await user.click(screen.getByRole('button', { name: /create/i }))

    await waitFor(() => expect(onSuccess).toHaveBeenCalledOnce())
  })
})
```

- [ ] **Step 6: Run tests — verify they fail**

```bash
cd frontend && npm run test -- --run
```

Expected: All tests FAIL — MSW not yet properly initialized.

- [ ] **Step 7: Initialize MSW**

```bash
npx msw init public/ --save
```

Expected: `public/mockServiceWorker.js` created, `package.json` updated with `msw.workerDirectory`.

- [ ] **Step 8: Run tests — verify they pass**

```bash
npm run test -- --run
```

Expected: All tests PASS.

- [ ] **Step 9: Commit**

```bash
cd ..
git add frontend/src/test/ frontend/src/features/todos/__tests__/ frontend/public/ frontend/package.json
git commit -m "feat: add Vitest + MSW tests for TodoList and TodoForm"
```

---

## Verify Full Stack

- [ ] **Step 1: Start both servers**

```bash
# Terminal 1 — Symfony
symfony server:start -d

# Terminal 2 — React
cd frontend && npm run dev
```

- [ ] **Step 2: Smoke test**

Open `http://localhost:5173`.
- Create a todo → appears in list
- Click status arrow → status changes
- Click Edit → form pre-filled
- Click Del → todo removed
- Use filters → list updates
