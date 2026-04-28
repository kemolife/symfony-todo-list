import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Pencil, Trash2, Calendar, AlertCircle } from 'lucide-react'
import { useDeleteTodo, useUpdateTodo } from '@/api/useTodos'
import { useModalStore } from '@/store/modalStore'
import type { Todo, TodoPriority, TodoStatus } from '@/types/todo'
import { toast } from 'sonner'
import { TodoItemList } from './TodoItemList'

const statusVariant: Record<TodoStatus, 'default' | 'secondary' | 'outline'> = {
  pending: 'outline',
  in_progress: 'secondary',
  done: 'default',
}

const statusLabel: Record<TodoStatus, string> = {
  pending: 'Pending',
  in_progress: 'In Progress',
  done: 'Done',
}

const priorityBorderClass: Record<TodoPriority, string> = {
  high:   'border-l-4 border-l-red-500',
  medium: 'border-l-4 border-l-orange-400',
  low:    'border-l-4 border-l-blue-400',
}

const priorityLabel: Record<TodoPriority, string> = {
  high: 'High', medium: 'Medium', low: 'Low',
}

function isOverdue(dueDate: string | null, status: TodoStatus): boolean {
  if (!dueDate || status === 'done') return false
  return new Date(dueDate) < new Date(new Date().toDateString())
}

interface TodoCardProps {
  todo: Todo
}

export function TodoCard({ todo }: TodoCardProps) {
  const updateTodo = useUpdateTodo()
  const deleteTodo = useDeleteTodo()
  const openEdit   = useModalStore((s) => s.openEdit)
  const overdue    = isOverdue(todo.dueDate, todo.status)

  const completedItems = todo.items.filter((i) => i.isCompleted).length
  const totalItems     = todo.items.length
  const progressPct    = totalItems > 0 ? (completedItems / totalItems) * 100 : 0

  const handleCheck = () => {
    const newStatus: TodoStatus = todo.status === 'done' ? 'pending' : 'done'
    updateTodo.mutate(
      {
        id: todo.id,
        name: todo.name,
        description: todo.description ?? undefined,
        tag: todo.tag ?? undefined,
        status: newStatus,
        priority: todo.priority,
        dueDate: todo.dueDate,
      },
      { onSuccess: () => toast.success(newStatus === 'done' ? 'Marked as done!' : 'Marked as pending') },
    )
  }

  const handleDelete = () => {
    deleteTodo.mutate(todo.id, {
      onSuccess: () => toast.success('Todo deleted'),
      onError:   (e) => toast.error((e as Error).message),
    })
  }

  return (
    <Card
      className={[
        'transition-shadow hover:shadow-md',
        priorityBorderClass[todo.priority],
        overdue ? 'bg-destructive/5' : '',
        todo.status === 'done' ? 'opacity-60' : '',
      ].join(' ')}
    >
      <CardContent className="flex items-start gap-4 p-4">
        <Checkbox
          checked={todo.status === 'done'}
          disabled={updateTodo.isPending}
          onCheckedChange={handleCheck}
          className="mt-0.5 shrink-0"
        />
        <div className="min-w-0 flex-1">
          <p className={`font-medium leading-snug ${todo.status === 'done' ? 'text-muted-foreground line-through' : ''}`}>
            {todo.name}
          </p>

          {todo.description && (
            <p className="mt-1 truncate text-sm text-muted-foreground">{todo.description}</p>
          )}

          <div className="mt-2 flex flex-wrap items-center gap-1.5">
            <Badge variant={statusVariant[todo.status]}>{statusLabel[todo.status]}</Badge>
            <Badge variant="outline" className="text-xs">{priorityLabel[todo.priority]}</Badge>
            {todo.tag && <Badge variant="outline">{todo.tag}</Badge>}

            {todo.dueDate && (
              <span className={`flex items-center gap-1 text-xs ${overdue ? 'font-medium text-destructive' : 'text-muted-foreground'}`}>
                {overdue ? <AlertCircle className="h-3 w-3" /> : <Calendar className="h-3 w-3" />}
                {overdue ? 'Overdue · ' : ''}{todo.dueDate}
              </span>
            )}
          </div>

          {totalItems > 0 && (
            <div className="mt-2 space-y-1">
              <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>Subtasks</span>
                <span>{completedItems}/{totalItems}</span>
              </div>
              <div className="h-1.5 w-full rounded-full bg-muted">
                <div
                  className="h-1.5 rounded-full bg-primary transition-all"
                  style={{ width: `${progressPct}%` }}
                />
              </div>
            </div>
          )}

          <TodoItemList todoId={todo.id} items={todo.items} />
        </div>

        <div className="flex shrink-0 gap-1">
          <Button size="icon" variant="ghost" onClick={() => openEdit(todo.id)} className="h-8 w-8">
            <Pencil className="h-3.5 w-3.5" />
          </Button>
          <Button
            size="icon"
            variant="ghost"
            onClick={handleDelete}
            disabled={deleteTodo.isPending}
            className="h-8 w-8 text-destructive hover:text-destructive"
          >
            <Trash2 className="h-3.5 w-3.5" />
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}
