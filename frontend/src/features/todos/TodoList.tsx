import { useTodos } from '@/api/useTodos'
import { useModalStore } from '@/store/modalStore'
import { useTodoFilterStore } from '@/store/todoFilterStore'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Plus, ClipboardList } from 'lucide-react'
import { TodoCard } from './TodoCard'
import { TodoFilters } from './TodoFilters'
import { TodoForm } from './TodoForm'

function TodoSkeleton() {
  return (
    <div className="flex items-start gap-4 rounded-lg border bg-card p-4">
      <Skeleton className="mt-0.5 h-4 w-4 rounded" />
      <div className="flex-1 space-y-2">
        <Skeleton className="h-4 w-3/4" />
        <Skeleton className="h-3 w-1/2" />
        <Skeleton className="h-5 w-20 rounded-full" />
      </div>
    </div>
  )
}

export function TodoList() {
  const filters = useTodoFilterStore((s) => s.filters)
  const { data: todos, isLoading, error } = useTodos(filters)
  const { isCreateOpen, editingTodoId, openCreate, close } = useModalStore()

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <ClipboardList className="h-6 w-6 text-primary" />
          <h1 className="text-2xl font-semibold tracking-tight">My Todos</h1>
          {todos && (
            <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
              {todos.length}
            </span>
          )}
        </div>
        <Button onClick={openCreate} className="gap-2">
          <Plus className="h-4 w-4" />
          New todo
        </Button>
      </div>

      <Separator />

      {/* Filters */}
      <TodoFilters />

      {/* Content */}
      {error && (
        <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
          {(error as Error).message}
        </div>
      )}

      {isLoading && (
        <div className="space-y-3">
          {Array.from({ length: 4 }).map((_, i) => <TodoSkeleton key={i} />)}
        </div>
      )}

      {!isLoading && todos?.length === 0 && (
        <div className="flex flex-col items-center gap-3 py-16 text-center">
          <ClipboardList className="h-12 w-12 text-muted-foreground/40" />
          <p className="text-muted-foreground">No todos found.</p>
          <Button variant="outline" onClick={openCreate} className="gap-2">
            <Plus className="h-4 w-4" />
            Create your first todo
          </Button>
        </div>
      )}

      <div className="space-y-2">
        {todos?.map((todo) => <TodoCard key={todo.id} todo={todo} />)}
      </div>

      {/* Create dialog */}
      <Dialog open={isCreateOpen} onOpenChange={(open) => !open && close()}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>New todo</DialogTitle>
          </DialogHeader>
          <TodoForm onSuccess={close} />
        </DialogContent>
      </Dialog>

      {/* Edit dialog */}
      <Dialog open={editingTodoId != null} onOpenChange={(open) => !open && close()}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit todo</DialogTitle>
          </DialogHeader>
          <TodoForm todoId={editingTodoId} onSuccess={close} />
        </DialogContent>
      </Dialog>
    </div>
  )
}
