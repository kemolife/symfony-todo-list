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
