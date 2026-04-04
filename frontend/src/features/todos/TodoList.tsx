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
      {error && <p className="text-red-600">{(error as Error).message}</p>}

      {todos?.length === 0 && !isLoading && (
        <p className="py-8 text-center text-gray-400">No todos found.</p>
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
