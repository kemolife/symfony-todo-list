import { useState } from 'react'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Pencil, Trash2, ShieldCheck, KeyRound } from 'lucide-react'
import { toast } from 'sonner'
import { useUsers, useCreateUser, useUpdateUser, useDeleteUser } from '@/api/useUsers'
import { UserApiKeysDialog } from './UserApiKeysDialog'
import { useAuthStore } from '@/store/authStore'
import type { User } from '@/types/user'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'

const STRONG_PASSWORD_REGEX =
  /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
const STRONG_PASSWORD_MESSAGE =
  'Min 8 chars with uppercase, lowercase, number and special character'

const createSchema = z.object({
  email: z.email(),
  password: z
    .string()
    .min(8, 'At least 8 characters')
    .regex(STRONG_PASSWORD_REGEX, STRONG_PASSWORD_MESSAGE),
  role: z.enum(['admin', 'user']),
})

const editSchema = z.object({
  email: z.email(),
  password: z.string().refine(
    (v) => v === '' || STRONG_PASSWORD_REGEX.test(v),
    STRONG_PASSWORD_MESSAGE,
  ),
  role: z.enum(['admin', 'user']),
})

type CreateFormData = z.infer<typeof createSchema>
type EditFormData = z.infer<typeof editSchema>

function RoleBadge({ role }: { role: string }) {
  const isAdmin = role === 'ROLE_ADMIN'
  return (
    <Badge variant={isAdmin ? 'default' : 'secondary'} className="text-xs">
      {role.replace('ROLE_', '')}
    </Badge>
  )
}

function CreateUserDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const createUser = useCreateUser()

  const {
    register,
    handleSubmit,
    reset,
    control,
    formState: { errors, isSubmitting },
  } = useForm<CreateFormData>({
    resolver: zodResolver(createSchema),
    defaultValues: { role: 'user' },
  })

  const onSubmit = async (data: CreateFormData) => {
    try {
      await createUser.mutateAsync(data)
      if (data.role === 'admin') {
        toast.success(`User created — enrollment email sent to ${data.email}`)
      } else {
        toast.success('User created')
      }
      reset()
      onClose()
    } catch {
      toast.error('Failed to create user')
    }
  }

  const handleClose = () => {
    reset()
    onClose()
  }

  return (
    <Dialog open={open} onOpenChange={(o) => !o && handleClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Create user</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 py-2">
          <div className="space-y-1.5">
            <Label htmlFor="create-email">Email</Label>
            <Input id="create-email" type="email" {...register('email')} />
            {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="create-password">Password</Label>
            <Input id="create-password" type="password" {...register('password')} />
            {errors.password && (
              <p className="text-xs text-destructive">{errors.password.message}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>Role</Label>
            <Controller
              name="role"
              control={control}
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="user">User</SelectItem>
                    <SelectItem value="admin">Admin</SelectItem>
                  </SelectContent>
                </Select>
              )}
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Creating…' : 'Create'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function EditUserDialog({ user, onClose }: { user: User | null; onClose: () => void }) {
  const updateUser = useUpdateUser()
  const currentEmail = useAuthStore((s) => s.email)
  const isSelf = user?.email === currentEmail

  const {
    register,
    handleSubmit,
    reset,
    control,
    formState: { errors, isSubmitting },
  } = useForm<EditFormData>({
    resolver: zodResolver(editSchema),
    values: user
      ? {
          email: user.email,
          password: '',
          role: user.roles.includes('ROLE_ADMIN') ? 'admin' : 'user',
        }
      : undefined,
  })

  const handleClose = () => {
    reset()
    onClose()
  }

  const onSubmit = async (data: EditFormData) => {
    if (!user) return
    try {
      const wasAdmin = user.roles.includes('ROLE_ADMIN')
      const payload = isSelf
        ? { id: user.id, email: data.email, password: data.password }
        : { id: user.id, ...data }
      await updateUser.mutateAsync(payload)
      const becomesAdmin = !isSelf && data.role === 'admin'
      if (!wasAdmin && becomesAdmin) {
        toast.success(`User updated — enrollment email sent to ${data.email}`)
      } else {
        toast.success('User updated')
      }
      handleClose()
    } catch {
      toast.error('Failed to update user')
    }
  }

  return (
    <Dialog open={user !== null} onOpenChange={(o) => !o && handleClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Edit user</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 py-2">
          <div className="space-y-1.5">
            <Label htmlFor="edit-email">Email</Label>
            <Input id="edit-email" type="email" {...register('email')} />
            {errors.email && (
              <p className="text-xs text-destructive">{errors.email.message}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="edit-password">New password</Label>
            <Input
              id="edit-password"
              type="password"
              placeholder="Leave blank to keep current"
              {...register('password')}
            />
            {errors.password && (
              <p className="text-xs text-destructive">{errors.password.message}</p>
            )}
          </div>

          {!isSelf && (
            <div className="space-y-1.5">
              <Label>Role</Label>
              <Controller
                name="role"
                control={control}
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="user">User</SelectItem>
                      <SelectItem value="admin">Admin</SelectItem>
                    </SelectContent>
                  </Select>
                )}
              />
            </div>
          )}

          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Saving…' : 'Save'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function DeleteUserDialog({ user, onClose }: { user: User | null; onClose: () => void }) {
  const deleteUser = useDeleteUser()
  const [isDeleting, setIsDeleting] = useState(false)

  const handleDelete = async () => {
    if (!user) return
    setIsDeleting(true)
    try {
      await deleteUser.mutateAsync(user.id)
      toast.success('User deleted')
      onClose()
    } catch {
      toast.error('Failed to delete user')
    } finally {
      setIsDeleting(false)
    }
  }

  return (
    <Dialog open={user !== null} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Delete user</DialogTitle>
        </DialogHeader>
        <p className="text-sm text-muted-foreground">
          Are you sure you want to delete <strong>{user?.email}</strong>? This action cannot be
          undone.
        </p>
        <DialogFooter>
          <Button variant="outline" onClick={onClose} disabled={isDeleting}>
            Cancel
          </Button>
          <Button variant="destructive" onClick={handleDelete} disabled={isDeleting}>
            {isDeleting ? 'Deleting…' : 'Delete'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export function UsersPage() {
  const { data: users, isLoading, isError } = useUsers()
  const [showCreate, setShowCreate] = useState(false)
  const [editUser, setEditUser] = useState<User | null>(null)
  const [deleteUser, setDeleteUser] = useState<User | null>(null)
  const [managingKeysUser, setManagingKeysUser] = useState<User | null>(null)

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Users</h2>
        <Button size="sm" onClick={() => setShowCreate(true)}>
          <Plus className="mr-1.5 h-4 w-4" />
          Add user
        </Button>
      </div>

      <div className="rounded-lg border bg-background">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50">
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">ID</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Email</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Roles</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">2FA</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Keys</th>
              <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody>
            {isLoading &&
              Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="border-b last:border-0">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-6" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-40" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-20" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-8" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-8" /></td>
                  <td className="px-4 py-3" />
                </tr>
              ))}

            {isError && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-sm text-destructive">
                  Failed to load users.
                </td>
              </tr>
            )}

            {users?.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-sm text-muted-foreground">
                  No users found.
                </td>
              </tr>
            )}

            {users?.map((user) => (
              <tr key={user.id} className="border-b last:border-0 hover:bg-muted/30">
                <td className="px-4 py-3 text-muted-foreground">{user.id}</td>
                <td className="px-4 py-3 font-medium">{user.email}</td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap gap-1">
                    {user.roles.map((role) => (
                      <RoleBadge key={role} role={role} />
                    ))}
                  </div>
                </td>
                <td className="px-4 py-3">
                  {user.hasTwoFactor ? (
                    <ShieldCheck className="h-4 w-4 text-green-600" />
                  ) : (
                    <span className="text-muted-foreground">—</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  {user.apiKeyCount > 0 ? (
                    <Badge variant="secondary" className="text-xs">
                      <KeyRound className="mr-1 h-3 w-3" />
                      {user.apiKeyCount}
                    </Badge>
                  ) : (
                    <span className="text-muted-foreground">—</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  <div className="flex justify-end gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8"
                      onClick={() => setEditUser(user)}
                    >
                      <Pencil className="h-3.5 w-3.5" />
                      <span className="sr-only">Edit</span>
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-muted-foreground"
                      onClick={() => setManagingKeysUser(user)}
                      title="Manage API keys"
                    >
                      <KeyRound className="h-3.5 w-3.5" />
                      <span className="sr-only">Manage API keys</span>
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-destructive hover:text-destructive"
                      onClick={() => setDeleteUser(user)}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                      <span className="sr-only">Delete</span>
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <CreateUserDialog open={showCreate} onClose={() => setShowCreate(false)} />
      <EditUserDialog user={editUser} onClose={() => setEditUser(null)} />
      <DeleteUserDialog user={deleteUser} onClose={() => setDeleteUser(null)} />
      <UserApiKeysDialog
        userId={managingKeysUser?.id ?? null}
        userEmail={managingKeysUser?.email ?? null}
        onClose={() => setManagingKeysUser(null)}
      />
    </div>
  )
}
