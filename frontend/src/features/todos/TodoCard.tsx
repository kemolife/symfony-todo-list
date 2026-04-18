import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Pencil, Trash2 } from 'lucide-react'
import { useDeleteTodo, useUpdateTodo } from '@/api/useTodos'
import { useModalStore } from '@/store/modalStore'
import type { Todo, TodoStatus } from '@/types/todo'
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

interface TodoCardProps {
  todo: Todo
}

export function TodoCard({ todo }: TodoCardProps) {
  const updateTodo = useUpdateTodo()
  const deleteTodo = useDeleteTodo()
  const openEdit = useModalStore((s) => s.openEdit)

  const handleCheck = () => {
    const newStatus: TodoStatus = todo.status === 'done' ? 'pending' : 'done'
    updateTodo.mutate(
      { id: todo.id, name: todo.name, description: todo.description ?? undefined, tag: todo.tag ?? undefined, status: newStatus },
      { onSuccess: () => toast.success(newStatus === 'done' ? 'Marked as done!' : 'Marked as pending') },
    )
  }

  const handleDelete = () => {
    deleteTodo.mutate(todo.id, {
      onSuccess: () => toast.success('Todo deleted'),
      onError: (e) => toast.error((e as Error).message),
    })
  }

  return (
    <Card className="transition-shadow hover:shadow-md">
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
          <div className="mt-2 flex flex-wrap gap-1.5">
            <Badge variant={statusVariant[todo.status]}>{statusLabel[todo.status]}</Badge>
            {todo.tag && <Badge variant="outline">{todo.tag}</Badge>}
          </div>
          <TodoItemList todoId={todo.id} items={todo.items} />
        </div>
        <div className="flex shrink-0 gap-1">
          <Button size="icon" variant="ghost" onClick={() => openEdit(todo.id)} className="h-8 w-8">
            <Pencil className="h-3.5 w-3.5" />
          </Button>
          <Button size="icon" variant="ghost" onClick={handleDelete} disabled={deleteTodo.isPending} className="h-8 w-8 text-destructive hover:text-destructive">
            <Trash2 className="h-3.5 w-3.5" />
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}
