import { useState } from 'react'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ChevronDown, ChevronUp, Plus, X } from 'lucide-react'
import { useCreateTodoItem, useDeleteTodoItem, useToggleTodoItem } from '@/api/useTodos'
import type { TodoItem } from '@/types/todo'
import { toast } from 'sonner'

interface TodoItemListProps {
  todoId: number
  items: TodoItem[]
}

export function TodoItemList({ todoId, items }: TodoItemListProps) {
  const [expanded, setExpanded] = useState(false)
  const [newTitle, setNewTitle] = useState('')

  const createItem = useCreateTodoItem()
  const toggleItem = useToggleTodoItem()
  const deleteItem = useDeleteTodoItem()

  const doneCount = items.filter((i) => i.isCompleted).length

  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault()
    const title = newTitle.trim()
    if (!title) return
    createItem.mutate(
      { todoId, title },
      {
        onSuccess: () => setNewTitle(''),
        onError: (e) => toast.error((e as Error).message),
      },
    )
  }

  const handleToggle = (item: TodoItem) => {
    toggleItem.mutate(
      { todoId, itemId: item.id, isCompleted: !item.isCompleted },
      { onError: (e) => toast.error((e as Error).message) },
    )
  }

  const handleDelete = (itemId: number) => {
    deleteItem.mutate(
      { todoId, itemId },
      { onError: (e) => toast.error((e as Error).message) },
    )
  }

  if (items.length === 0 && !expanded) {
    return (
      <button
        onClick={() => setExpanded(true)}
        className="mt-2 flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
      >
        <Plus className="h-3 w-3" />
        Add checklist
      </button>
    )
  }

  return (
    <div className="mt-3 border-t pt-2">
      <button
        onClick={() => setExpanded((v) => !v)}
        className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
      >
        {expanded ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
        <span>
          {items.length} {items.length === 1 ? 'item' : 'items'}
        </span>
        {items.length > 0 && (
          <span className="text-xs opacity-70">
            · {doneCount}/{items.length} done
          </span>
        )}
      </button>

      {expanded && (
        <div className="mt-2 space-y-1.5">
          {items.map((item) => (
            <div key={item.id} className="group flex items-center gap-2">
              <Checkbox
                checked={item.isCompleted}
                disabled={toggleItem.isPending}
                onCheckedChange={() => handleToggle(item)}
                className="shrink-0"
              />
              <span
                className={`flex-1 text-sm ${item.isCompleted ? 'text-muted-foreground line-through' : ''}`}
              >
                {item.title}
              </span>
              <Button
                size="icon"
                variant="ghost"
                className="h-5 w-5 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
                disabled={deleteItem.isPending}
                onClick={() => handleDelete(item.id)}
              >
                <X className="h-3 w-3" />
              </Button>
            </div>
          ))}

          <form onSubmit={handleAdd} className="flex items-center gap-2 pt-1">
            <Input
              value={newTitle}
              onChange={(e) => setNewTitle(e.target.value)}
              placeholder="Add item…"
              className="h-7 text-sm"
              disabled={createItem.isPending}
            />
            <Button
              type="submit"
              size="sm"
              variant="outline"
              className="h-7 shrink-0 px-2"
              disabled={createItem.isPending || newTitle.trim() === ''}
            >
              <Plus className="h-3 w-3" />
            </Button>
          </form>
        </div>
      )}
    </div>
  )
}
