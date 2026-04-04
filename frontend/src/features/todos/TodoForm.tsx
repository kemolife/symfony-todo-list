import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateTodo, useTodo, useUpdateTodo } from '@/api/useTodos'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { toast } from 'sonner'

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
    setValue,
    watch,
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
    try {
      if (isEdit && todoId != null) {
        await updateTodo.mutateAsync({ id: todoId, ...data })
        toast.success('Todo updated')
      } else {
        await createTodo.mutateAsync(data)
        toast.success('Todo created')
      }
      onSuccess()
    } catch (e) {
      toast.error((e as Error).message)
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="space-y-1.5">
        <Label htmlFor="name">Name *</Label>
        <Input id="name" {...register('name')} placeholder="What needs to be done?" />
        {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="description">Description</Label>
        <Textarea id="description" {...register('description')} placeholder="Add details..." rows={3} />
        {errors.description && <p className="text-sm text-destructive">{errors.description.message}</p>}
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="tag">Tag</Label>
        <Input id="tag" {...register('tag')} placeholder="e.g. work, personal, shopping" />
        {errors.tag && <p className="text-sm text-destructive">{errors.tag.message}</p>}
      </div>

      {isEdit && (
        <div className="space-y-1.5">
          <Label>Status</Label>
          <Select value={watch('status')} onValueChange={(v) => setValue('status', v as FormData['status'])}>
            <SelectTrigger>
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="in_progress">In Progress</SelectItem>
              <SelectItem value="done">Done</SelectItem>
            </SelectContent>
          </Select>
        </div>
      )}

      <div className="flex justify-end gap-2 pt-2">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving...' : isEdit ? 'Update todo' : 'Create todo'}
        </Button>
      </div>
    </form>
  )
}
