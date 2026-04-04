import { useTodos } from '@/api/useTodos'
import { useModalStore } from '@/store/modalStore'
import { useTodoFilterStore } from '@/store/todoFilterStore'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Plus, ClipboardList, ChevronLeft, ChevronRight } from 'lucide-react'
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

function PaginationControls({ page, pages, onPageChange }: { page: number; pages: number; onPageChange: (p: number) => void }) {
  if (pages <= 1) return null

  const getPageNumbers = () => {
    const delta = 2
    const range: number[] = []
    const left = Math.max(2, page - delta)
    const right = Math.min(pages - 1, page + delta)

    range.push(1)
    if (left > 2) range.push(-1) // left ellipsis
    for (let i = left; i <= right; i++) range.push(i)
    if (right < pages - 1) range.push(-2) // right ellipsis
    if (pages > 1) range.push(pages)

    return range
  }

  return (
    <div className="flex items-center justify-center gap-1 pt-2">
      <Button
        variant="outline"
        size="sm"
        onClick={() => onPageChange(page - 1)}
        disabled={page <= 1}
        className="h-8 w-8 p-0"
      >
        <ChevronLeft className="h-4 w-4" />
      </Button>

      {getPageNumbers().map((p, i) =>
        p < 0 ? (
          <span key={p + '_' + i} className="px-1 text-muted-foreground">…</span>
        ) : (
          <Button
            key={p}
            variant={p === page ? 'default' : 'outline'}
            size="sm"
            onClick={() => onPageChange(p)}
            className="h-8 w-8 p-0"
          >
            {p}
          </Button>
        ),
      )}

      <Button
        variant="outline"
        size="sm"
        onClick={() => onPageChange(page + 1)}
        disabled={page >= pages}
        className="h-8 w-8 p-0"
      >
        <ChevronRight className="h-4 w-4" />
      </Button>
    </div>
  )
}

export function TodoList() {
  const filters = useTodoFilterStore((s) => s.filters)
  const setPage = useTodoFilterStore((s) => s.setPage)
  const { data: paginated, isLoading, error } = useTodos(filters)
  const { isCreateOpen, editingTodoId, openCreate, close } = useModalStore()

  const todos = paginated?.items
  const page = paginated?.page ?? 1
  const pages = paginated?.pages ?? 1
  const total = paginated?.total ?? 0

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <ClipboardList className="h-6 w-6 text-primary" />
          <h1 className="text-2xl font-semibold tracking-tight">My Todos</h1>
          {paginated && (
            <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
              {total}
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

      {/* Pagination */}
      <PaginationControls page={page} pages={pages} onPageChange={setPage} />

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
