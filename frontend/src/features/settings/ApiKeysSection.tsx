import { useApiKeys, useCreateApiKey, useRevokeApiKey } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { CopyableKey } from '@/components/CopyableKey'
import { Trash2 } from 'lucide-react'
import { useState } from 'react'
import { toast } from 'sonner'
import type { ApiKeyPermission } from '@/types/apiKey'

const PERMISSIONS: { value: ApiKeyPermission; label: string; hint: string }[] = [
  { value: 'read',   label: 'Read',   hint: 'View todos' },
  { value: 'write',  label: 'Write',  hint: 'Create and update' },
  { value: 'delete', label: 'Delete', hint: 'Delete todos' },
]

function formatDate(iso: string | null): string {
  if (!iso) return 'Never'
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}

export function ApiKeysSection() {
  const { data: keys = [], isLoading } = useApiKeys()
  const createKey = useCreateApiKey()
  const revokeKey = useRevokeApiKey()

  const [name, setName] = useState('')
  const [permissions, setPermissions] = useState<ApiKeyPermission[]>(['read'])
  const [newKeyValue, setNewKeyValue] = useState<string | null>(null)
  const [revokeTarget, setRevokeTarget] = useState<number | null>(null)

  const togglePermission = (p: ApiKeyPermission) => {
    setPermissions((prev) =>
      prev.includes(p) ? prev.filter((x) => x !== p) : [...prev, p],
    )
  }

  const handleCreate = async () => {
    if (!name.trim() || permissions.length === 0) return
    try {
      const result = await createKey.mutateAsync({ name: name.trim(), permissions })
      setNewKeyValue(result.keyValue ?? null)
      setName('')
      setPermissions(['read'])
      toast.success('API key created')
    } catch {
      toast.error('Failed to create key')
    }
  }

  const handleRevoke = async (id: number) => {
    try {
      await revokeKey.mutateAsync(id)
      setRevokeTarget(null)
      if (newKeyValue) setNewKeyValue(null)
      toast.success('Key revoked')
    } catch {
      toast.error('Failed to revoke key')
    }
  }

  return (
    <div className="space-y-6">
      {newKeyValue && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30 space-y-2">
          <p className="text-xs font-medium text-amber-700 dark:text-amber-400">
            Copy this key now — it will not be shown again.
          </p>
          <CopyableKey value={newKeyValue} />
        </div>
      )}

      {isLoading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : keys.length === 0 ? (
        <p className="text-sm text-muted-foreground">No API keys yet.</p>
      ) : (
        <div className="space-y-2">
          {keys.map((key) => (
            <div key={key.id} className="rounded-lg border bg-background px-3 py-2.5 space-y-1.5">
              <div className="flex items-start justify-between gap-2">
                <div className="space-y-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-medium text-sm">{key.name}</span>
                    <code className="text-xs text-muted-foreground font-mono">{key.prefix}…</code>
                  </div>
                  {key.description && <p className="text-xs text-muted-foreground">{key.description}</p>}
                  <div className="flex flex-wrap gap-1">
                    {key.permissions.map((p) => (
                      <Badge key={p} variant="outline" className="text-xs capitalize">{p}</Badge>
                    ))}
                  </div>
                  <p className="text-xs text-muted-foreground">Last used: {formatDate(key.lastUsedAt)}</p>
                </div>
                <div className="shrink-0">
                  {revokeTarget === key.id ? (
                    <div className="flex items-center gap-1.5">
                      <Button size="sm" variant="destructive" className="h-7 text-xs" onClick={() => handleRevoke(key.id)} disabled={revokeKey.isPending}>
                        {revokeKey.isPending ? 'Revoking…' : 'Confirm'}
                      </Button>
                      <Button size="sm" variant="outline" className="h-7 text-xs" onClick={() => setRevokeTarget(null)}>Cancel</Button>
                    </div>
                  ) : (
                    <Button variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-destructive" onClick={() => setRevokeTarget(key.id)}>
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <div className="rounded-lg border p-4 space-y-3">
        <p className="text-sm font-medium">Create new key</p>
        <Input placeholder="Key name (e.g. CI pipeline)" value={name} onChange={(e) => setName(e.target.value)} className="max-w-xs" />
        <div className="space-y-2">
          {PERMISSIONS.map(({ value, label, hint }) => (
            <div key={value} className="flex items-center gap-2.5">
              <Checkbox
                checked={permissions.includes(value)}
                onCheckedChange={() => togglePermission(value)}
              />
              <span className="text-sm font-medium">{label}</span>
              <span className="text-xs text-muted-foreground">{hint}</span>
            </div>
          ))}
        </div>
        <Button onClick={handleCreate} disabled={createKey.isPending || !name.trim() || permissions.length === 0}>
          {createKey.isPending ? 'Creating…' : 'Create key'}
        </Button>
      </div>

      <div className="rounded-md bg-muted p-3 text-xs text-muted-foreground space-y-1">
        <p className="font-medium text-foreground">Usage</p>
        <code className="block text-foreground">X-Api-Key: {'<your-key>'}</code>
        <p>Rate limited to 1 000 req / hour.</p>
      </div>
    </div>
  )
}
