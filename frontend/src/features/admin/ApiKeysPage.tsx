import { useState } from 'react'
import { Copy, Check, RefreshCw, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { useGenerateApiKey, useRevokeApiKey } from '@/api/useApiKey'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'

function CopyableKey({ apiKey }: { apiKey: string }) {
  const [copied, setCopied] = useState(false)

  const handleCopy = () => {
    navigator.clipboard.writeText(apiKey)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="flex items-center gap-2 rounded-md border bg-muted px-3 py-2">
      <code className="flex-1 break-all font-mono text-sm">{apiKey}</code>
      <Button variant="ghost" size="icon" className="h-8 w-8 shrink-0" onClick={handleCopy}>
        {copied ? (
          <Check className="h-4 w-4 text-green-600" />
        ) : (
          <Copy className="h-4 w-4" />
        )}
        <span className="sr-only">Copy</span>
      </Button>
    </div>
  )
}

function RevokeDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const revoke = useRevokeApiKey()

  const handleRevoke = async () => {
    try {
      await revoke.mutateAsync()
      toast.success('API key revoked')
      onClose()
    } catch {
      toast.error('Failed to revoke API key')
    }
  }

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Revoke API key</DialogTitle>
        </DialogHeader>
        <p className="text-sm text-muted-foreground">
          Existing integrations using this key will immediately stop working.
        </p>
        <DialogFooter>
          <Button variant="outline" onClick={onClose} disabled={revoke.isPending}>
            Cancel
          </Button>
          <Button variant="destructive" onClick={handleRevoke} disabled={revoke.isPending}>
            {revoke.isPending ? 'Revoking…' : 'Revoke'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export function ApiKeysPage() {
  const generate = useGenerateApiKey()
  const [generatedKey, setGeneratedKey] = useState<string | null>(null)
  const [showRevoke, setShowRevoke] = useState(false)

  const handleGenerate = async () => {
    try {
      const result = await generate.mutateAsync()
      setGeneratedKey(result.apiKey)
      toast.success('API key generated')
    } catch {
      toast.error('Failed to generate API key')
    }
  }

  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold">API Keys</h2>

      <div className="rounded-lg border bg-background p-6 space-y-4">
        <div>
          <h3 className="font-medium">External API access</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            Use an API key to authenticate requests from scripts or integrations. Rate limited to
            1 000 requests / hour.
          </p>
        </div>

        {generatedKey && (
          <div className="space-y-2">
            <p className="text-sm font-medium text-amber-600">
              Copy this key now — it will not be shown again.
            </p>
            <CopyableKey apiKey={generatedKey} />
          </div>
        )}

        <div className="flex gap-2">
          <Button onClick={handleGenerate} disabled={generate.isPending}>
            <RefreshCw className="mr-1.5 h-4 w-4" />
            {generate.isPending ? 'Generating…' : generatedKey ? 'Regenerate' : 'Generate API key'}
          </Button>
          <Button variant="outline" className="text-destructive hover:text-destructive" onClick={() => setShowRevoke(true)}>
            <Trash2 className="mr-1.5 h-4 w-4" />
            Revoke
          </Button>
        </div>

        <div className="rounded-md bg-muted p-3 text-xs text-muted-foreground space-y-1">
          <p className="font-medium">Usage</p>
          <code className="block">X-Api-Key: {'<your-key>'}</code>
          <p className="mt-1">Add this header to any API request instead of a JWT token.</p>
        </div>
      </div>

      <RevokeDialog open={showRevoke} onClose={() => setShowRevoke(false)} />
    </div>
  )
}
