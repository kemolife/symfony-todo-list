import { useState } from 'react'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2, X } from 'lucide-react'
import { toast } from 'sonner'
import { useApiKeys, useCreateApiKey, useRevokeApiKey } from '@/api/useApiKey'
import type { ApiKeyEntry, ApiKeyPermission } from '@/types/apiKey'
import { CopyableKey } from '@/components/CopyableKey'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

const PERMISSIONS: { value: ApiKeyPermission; label: string; hint: string }[] = [
  { value: 'read', label: 'Read', hint: 'View todos and items' },
  { value: 'write', label: 'Write', hint: 'Create and update todos' },
  { value: 'delete', label: 'Delete', hint: 'Delete todos' },
]

const createKeySchema = z.object({
  name: z.string().min(1, 'Name is required').max(100),
  description: z.string().max(65535).optional(),
  permissions: z
    .array(z.enum(['read', 'write', 'delete']))
    .min(1, 'Select at least one permission'),
})

type CreateKeyForm = z.infer<typeof createKeySchema>

function formatDate(iso: string | null): string {
  if (!iso) return 'Never'
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}

export function ApiKeyDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { data: keys = [], isLoading } = useApiKeys()
  const createKey = useCreateApiKey()
  const revokeKey = useRevokeApiKey()

  const [showCreateForm, setShowCreateForm] = useState(false)
  const [justCreatedKey, setJustCreatedKey] = useState<ApiKeyEntry | null>(null)
  const [revokeTarget, setRevokeTarget] = useState<number | null>(null)

  const {
    register,
    handleSubmit,
    control,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<CreateKeyForm>({
    resolver: zodResolver(createKeySchema),
    defaultValues: { name: '', description: '', permissions: ['read'] },
  })

  const handleCreate = async (data: CreateKeyForm) => {
    try {
      const result = await createKey.mutateAsync(data)
      setJustCreatedKey(result)
      setShowCreateForm(false)
      reset()
      toast.success('API key created')
    } catch {
      toast.error('Failed to create API key')
    }
  }

  const handleRevoke = async (id: number) => {
    try {
      await revokeKey.mutateAsync(id)
      setRevokeTarget(null)
      if (justCreatedKey?.id === id) setJustCreatedKey(null)
      toast.success('API key revoked')
    } catch {
      toast.error('Failed to revoke API key')
    }
  }

  const handleClose = () => {
    setShowCreateForm(false)
    setJustCreatedKey(null)
    setRevokeTarget(null)
    reset()
    onClose()
  }

  return (
    <Dialog open={open} onOpenChange={(o) => !o && handleClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <div className="flex items-center justify-between pr-6">
            <DialogTitle>API Keys</DialogTitle>
            {!showCreateForm && (
              <Button size="sm" onClick={() => setShowCreateForm(true)}>
                <Plus className="mr-1.5 h-3.5 w-3.5" />
                New Key
              </Button>
            )}
          </div>
        </DialogHeader>

        <div className="space-y-4 py-1">
          {/* Create form */}
          {showCreateForm && (
            <form onSubmit={handleSubmit(handleCreate)} className="rounded-lg border p-4 space-y-3">
              <div className="space-y-1.5">
                <Label htmlFor="key-name">Name</Label>
                <Input id="key-name" placeholder="e.g. CI pipeline" {...register('name')} />
                {errors.name && (
                  <p className="text-xs text-destructive">{errors.name.message}</p>
                )}
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="key-desc">Description</Label>
                <textarea
                  id="key-desc"
                  placeholder="Optional"
                  rows={3}
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                  {...register('description')}
                />
              </div>

              <div className="space-y-1.5">
                <Label>Permissions</Label>
                <Controller
                  name="permissions"
                  control={control}
                  render={({ field }) => (
                    <div className="space-y-2">
                      {PERMISSIONS.map(({ value, label, hint }) => (
                        <div key={value} className="flex items-center gap-2.5">
                          <Checkbox
                            checked={field.value.includes(value)}
                            onCheckedChange={(checked) =>
                              field.onChange(
                                checked
                                  ? [...field.value, value]
                                  : field.value.filter((p) => p !== value),
                              )
                            }
                          />
                          <div>
                            <span className="text-sm font-medium">{label}</span>
                            <span className="ml-1.5 text-xs text-muted-foreground">{hint}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                />
                {errors.permissions && (
                  <p className="text-xs text-destructive">{errors.permissions.message}</p>
                )}
              </div>

              <div className="flex gap-2 pt-1">
                <Button type="submit" size="sm" disabled={isSubmitting}>
                  {isSubmitting ? 'Creating…' : 'Create'}
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  onClick={() => {
                    setShowCreateForm(false)
                    reset()
                  }}
                >
                  Cancel
                </Button>
              </div>
            </form>
          )}

          {/* New key banner */}
          {justCreatedKey?.keyValue && (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 space-y-2 dark:border-amber-900 dark:bg-amber-950/30">
              <div className="flex items-center justify-between">
                <p className="text-xs font-medium text-amber-700 dark:text-amber-400">
                  Copy this key now — it will not be shown again.
                </p>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6"
                  onClick={() => setJustCreatedKey(null)}
                >
                  <X className="h-3.5 w-3.5" />
                </Button>
              </div>
              <CopyableKey value={justCreatedKey.keyValue} />
            </div>
          )}

          {/* Keys list */}
          {isLoading ? (
            <p className="text-sm text-muted-foreground">Loading…</p>
          ) : keys.length === 0 ? (
            <p className="text-sm text-muted-foreground py-2">
              No API keys yet. Create one to authenticate scripts.
            </p>
          ) : (
            <div className="space-y-2">
              {keys.map((key) => (
                <div
                  key={key.id}
                  className="rounded-lg border bg-background px-3 py-2.5 space-y-2"
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="space-y-1 min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-medium text-sm">{key.name}</span>
                        <code className="text-xs text-muted-foreground font-mono">
                          {key.prefix}…
                        </code>
                      </div>
                      {key.description && (
                        <p className="text-xs text-muted-foreground">{key.description}</p>
                      )}
                      <div className="flex flex-wrap gap-1">
                        {key.permissions.map((p) => (
                          <Badge key={p} variant="outline" className="text-xs capitalize">
                            {p}
                          </Badge>
                        ))}
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Last used: {formatDate(key.lastUsedAt)}
                      </p>
                    </div>

                    <div className="shrink-0">
                      {revokeTarget === key.id ? (
                        <div className="flex items-center gap-1.5">
                          <Button
                            size="sm"
                            variant="destructive"
                            className="h-7 text-xs"
                            onClick={() => handleRevoke(key.id)}
                            disabled={revokeKey.isPending}
                          >
                            {revokeKey.isPending ? 'Revoking…' : 'Confirm'}
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            className="h-7 text-xs"
                            onClick={() => setRevokeTarget(null)}
                          >
                            Cancel
                          </Button>
                        </div>
                      ) : (
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-7 w-7 text-muted-foreground hover:text-destructive"
                          onClick={() => setRevokeTarget(key.id)}
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                          <span className="sr-only">Revoke</span>
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Usage hint */}
          <div className="rounded-md bg-muted p-3 text-xs text-muted-foreground space-y-1">
            <p className="font-medium text-foreground">Usage</p>
            <code className="block text-foreground">X-Api-Key: {'<your-key>'}</code>
            <p>Rate limited to 1 000 req / hour.</p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  )
}
