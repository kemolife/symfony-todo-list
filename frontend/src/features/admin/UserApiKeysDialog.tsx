import { useState } from 'react'
import { Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { useAdminUserApiKeys, useAdminRevokeApiKey } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}

interface Props {
  userId: number | null
  userEmail: string | null
  onClose: () => void
}

export function UserApiKeysDialog({ userId, userEmail, onClose }: Props) {
  const { data: keys = [], isLoading } = useAdminUserApiKeys(userId)
  const revokeKey = useAdminRevokeApiKey()
  const [revokeTarget, setRevokeTarget] = useState<number | null>(null)

  const handleRevoke = async (id: number) => {
    try {
      await revokeKey.mutateAsync(id)
      setRevokeTarget(null)
      toast.success('API key revoked')
    } catch {
      toast.error('Failed to revoke API key')
    }
  }

  const handleClose = () => {
    setRevokeTarget(null)
    onClose()
  }

  return (
    <Dialog open={userId !== null} onOpenChange={(o) => !o && handleClose()}>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>API Keys — {userEmail}</DialogTitle>
        </DialogHeader>

        <div className="py-2">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50 text-muted-foreground">
                <th className="px-3 py-2 text-left font-medium">Name</th>
                <th className="px-3 py-2 text-left font-medium">Prefix</th>
                <th className="px-3 py-2 text-left font-medium">Permissions</th>
                <th className="px-3 py-2 text-left font-medium">Last Used</th>
                <th className="px-3 py-2 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading &&
                Array.from({ length: 2 }).map((_, i) => (
                  <tr key={i} className="border-b last:border-0">
                    <td className="px-3 py-2"><Skeleton className="h-4 w-24" /></td>
                    <td className="px-3 py-2"><Skeleton className="h-4 w-16" /></td>
                    <td className="px-3 py-2"><Skeleton className="h-4 w-20" /></td>
                    <td className="px-3 py-2"><Skeleton className="h-4 w-16" /></td>
                    <td className="px-3 py-2" />
                  </tr>
                ))}

              {!isLoading && keys.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-3 py-6 text-center text-muted-foreground">
                    No API keys for this user.
                  </td>
                </tr>
              )}

              {keys.map((key) => (
                <tr key={key.id} className="border-b last:border-0 hover:bg-muted/30">
                  <td className="px-3 py-2 font-medium">{key.name}</td>
                  <td className="px-3 py-2">
                    <code className="font-mono text-xs text-muted-foreground">{key.prefix}…</code>
                  </td>
                  <td className="px-3 py-2">
                    <div className="flex flex-wrap gap-1">
                      {key.permissions.map((p) => (
                        <Badge key={p} variant="outline" className="text-xs capitalize">
                          {p}
                        </Badge>
                      ))}
                    </div>
                  </td>
                  <td className="px-3 py-2 text-muted-foreground">{formatDate(key.lastUsedAt)}</td>
                  <td className="px-3 py-2">
                    <div className="flex justify-end gap-1.5">
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
                          className="h-7 w-7 text-muted-foreground hover:text-destructive"
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
      </DialogContent>
    </Dialog>
  )
}
