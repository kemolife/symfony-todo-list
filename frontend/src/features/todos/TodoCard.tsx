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
      <div className="mr-4 min-w-0 flex-1">
        <p className={`font-medium ${todo.status === 'done' ? 'text-gray-400 line-through' : ''}`}>
          {todo.name}
        </p>
        {todo.description && (
          <p className="mt-1 truncate text-sm text-gray-500">{todo.description}</p>
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
