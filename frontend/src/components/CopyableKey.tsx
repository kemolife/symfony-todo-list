import { useState } from 'react'
import { Copy, Check } from 'lucide-react'
import { Button } from '@/components/ui/button'

export function CopyableKey({ value }: { value: string }) {
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
        {copied ? (
          <Check className="h-3.5 w-3.5 text-green-600" />
        ) : (
          <Copy className="h-3.5 w-3.5" />
        )}
        <span className="sr-only">Copy</span>
      </Button>
    </div>
  )
}
