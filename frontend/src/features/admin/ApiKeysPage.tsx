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
import { Skeleton } from '@/components/ui/skeleton'

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
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}

export function ApiKeysPage() {
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

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">API Keys</h2>
        {!showCreateForm && (
          <Button size="sm" onClick={() => setShowCreateForm(true)}>
            <Plus className="mr-1.5 h-4 w-4" />
            New API Key
          </Button>
        )}
      </div>

      {/* Description */}
      <div className="rounded-lg border bg-background p-4 text-sm text-muted-foreground space-y-1">
        <p>
          Use API keys to authenticate requests from scripts or integrations via the{' '}
          <code className="text-foreground">X-Api-Key</code> header. Rate limited to{' '}
          <strong className="text-foreground">1 000 req / hour</strong> per key.
        </p>
      </div>

      {/* Create form */}
      {showCreateForm && (
        <div className="rounded-lg border bg-background p-5 space-y-4">
          <h3 className="font-medium">New API key</h3>
          <form onSubmit={handleSubmit(handleCreate)} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="key-name">Name</Label>
              <Input
                id="key-name"
                placeholder="e.g. CI pipeline"
                className="max-w-sm"
                {...register('name')}
              />
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
                className="w-full max-w-sm rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
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

            <div className="flex gap-2">
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? 'Creating…' : 'Create key'}
              </Button>
              <Button
                type="button"
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
        </div>
      )}

      {/* New key banner */}
      {justCreatedKey?.keyValue && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 space-y-2 dark:border-amber-900 dark:bg-amber-950/30">
          <div className="flex items-center justify-between">
            <p className="text-sm font-medium text-amber-700 dark:text-amber-400">
              Copy this key now — it will not be shown again.
            </p>
            <Button
              variant="ghost"
              size="icon"
              className="h-7 w-7"
              onClick={() => setJustCreatedKey(null)}
            >
              <X className="h-4 w-4" />
            </Button>
          </div>
          <CopyableKey value={justCreatedKey.keyValue} />
        </div>
      )}

      {/* Keys table */}
      <div className="rounded-lg border bg-background">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50">
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Prefix</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Permissions</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Last Used</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Created</th>
              <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody>
            {isLoading &&
              Array.from({ length: 2 }).map((_, i) => (
                <tr key={i} className="border-b last:border-0">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-28" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-20" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-24" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-16" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-20" /></td>
                  <td className="px-4 py-3" />
                </tr>
              ))}

            {!isLoading && keys.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-sm text-muted-foreground">
                  No API keys yet. Create one to get started.
                </td>
              </tr>
            )}

            {keys.map((key) => (
              <tr key={key.id} className="border-b last:border-0 hover:bg-muted/30">
                <td className="px-4 py-3 font-medium">{key.name}</td>
                <td className="px-4 py-3">
                  <code className="font-mono text-xs text-muted-foreground">{key.prefix}…</code>
                </td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap gap-1">
                    {key.permissions.map((p) => (
                      <Badge key={p} variant="outline" className="text-xs capitalize">
                        {p}
                      </Badge>
                    ))}
                  </div>
                </td>
                <td className="px-4 py-3 text-muted-foreground">{formatDate(key.lastUsedAt)}</td>
                <td className="px-4 py-3 text-muted-foreground">{formatDate(key.createdAt)}</td>
                <td className="px-4 py-3">
                  <div className="flex justify-end gap-2">
                    {revokeTarget === key.id ? (
                      <>
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
                      </>
                    ) : (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-muted-foreground hover:text-destructive"
                        onClick={() => setRevokeTarget(key.id)}
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                        <span className="sr-only">Revoke</span>
                      </Button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
