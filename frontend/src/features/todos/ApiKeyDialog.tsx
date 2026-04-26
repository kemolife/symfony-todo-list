import { useState } from 'react'
import { Copy, Check, RefreshCw, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { useProfile, useGenerateApiKey, useRevokeApiKey } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

function CopyableKey({ value }: { value: string }) {
  const [copied, setCopied] = useState(false)

  const handleCopy = () => {
    navigator.clipboard.writeText(value)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="flex items-center gap-2 rounded-md border bg-muted px-3 py-2">
      <code className="flex-1 break-all font-mono text-xs">{value}</code>
      <Button variant="ghost" size="icon" className="h-7 w-7 shrink-0" onClick={handleCopy}>
        {copied ? <Check className="h-3.5 w-3.5 text-green-600" /> : <Copy className="h-3.5 w-3.5" />}
        <span className="sr-only">Copy</span>
      </Button>
    </div>
  )
}

export function ApiKeyDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { data: profile } = useProfile()
  const generate = useGenerateApiKey()
  const revoke = useRevokeApiKey()
  const [newKey, setNewKey] = useState<string | null>(null)
  const [confirmRevoke, setConfirmRevoke] = useState(false)

  const handleGenerate = async () => {
    try {
      const result = await generate.mutateAsync()
      setNewKey(result.apiKey)
      toast.success('API key generated')
    } catch {
      toast.error('Failed to generate API key')
    }
  }

  const handleRevoke = async () => {
    try {
      await revoke.mutateAsync()
      setNewKey(null)
      setConfirmRevoke(false)
      toast.success('API key revoked')
    } catch {
      toast.error('Failed to revoke API key')
    }
  }

  const handleClose = () => {
    setNewKey(null)
    setConfirmRevoke(false)
    onClose()
  }

  const hasKey = profile?.hasApiKey ?? false

  return (
    <Dialog open={open} onOpenChange={(o) => !o && handleClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>API Key</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-1">
          <p className="text-sm text-muted-foreground">
            Use an API key to access your todos from scripts or integrations.{' '}
            <span className="font-medium text-foreground">Rate limited to 1 000 req / hour.</span>
          </p>

          <div className="flex items-center gap-2 text-sm">
            <span className="text-muted-foreground">Status:</span>
            {hasKey ? (
              <span className="font-medium text-green-600">Active</span>
            ) : (
              <span className="font-medium text-muted-foreground">No key</span>
            )}
          </div>

          {newKey && (
            <div className="space-y-1.5">
              <p className="text-xs font-medium text-amber-600">
                Copy this key now — it will not be shown again.
              </p>
              <CopyableKey value={newKey} />
            </div>
          )}

          {!confirmRevoke ? (
            <div className="flex gap-2">
              <Button size="sm" onClick={handleGenerate} disabled={generate.isPending}>
                <RefreshCw className="mr-1.5 h-3.5 w-3.5" />
                {generate.isPending ? 'Generating…' : hasKey ? 'Regenerate' : 'Generate'}
              </Button>
              {hasKey && (
                <Button
                  size="sm"
                  variant="outline"
                  className="text-destructive hover:text-destructive"
                  onClick={() => setConfirmRevoke(true)}
                >
                  <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                  Revoke
                </Button>
              )}
            </div>
          ) : (
            <div className="space-y-2">
              <p className="text-sm text-destructive">Revoke your API key? Existing integrations will stop working.</p>
              <div className="flex gap-2">
                <Button size="sm" variant="destructive" onClick={handleRevoke} disabled={revoke.isPending}>
                  {revoke.isPending ? 'Revoking…' : 'Confirm revoke'}
                </Button>
                <Button size="sm" variant="outline" onClick={() => setConfirmRevoke(false)}>
                  Cancel
                </Button>
              </div>
            </div>
          )}

          <div className="rounded-md bg-muted p-3 text-xs text-muted-foreground space-y-1">
            <p className="font-medium text-foreground">Usage</p>
            <code className="block">X-Api-Key: {'<your-key>'}</code>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  )
}
