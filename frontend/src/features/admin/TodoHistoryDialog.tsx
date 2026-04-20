import { History } from 'lucide-react'
import { useAdminTodoHistory, type AuditLogEntry } from '@/api/useAdminTodos'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Skeleton } from '@/components/ui/skeleton'

const ACTION_VARIANT: Record<AuditLogEntry['action'], 'default' | 'secondary' | 'destructive'> = {
  created: 'default',
  updated: 'secondary',
  deleted: 'destructive',
}

function ChangeRow({ field, from, to }: { field: string; from: unknown; to: unknown }) {
  const fmt = (v: unknown) => (v === null || v === undefined ? '—' : String(v))
  return (
    <div className="flex items-center gap-2 text-xs">
      <span className="w-24 shrink-0 font-mono text-muted-foreground">{field}</span>
      <span className="text-muted-foreground line-through">{fmt(from)}</span>
      <span className="text-foreground">→ {fmt(to)}</span>
    </div>
  )
}

function EntryRow({ entry }: { entry: AuditLogEntry }) {
  return (
    <div className="flex gap-3 py-3 border-b last:border-0">
      <div className="flex flex-col items-center gap-1 pt-0.5">
        <div className="h-2 w-2 rounded-full bg-border mt-1" />
      </div>
      <div className="flex-1 space-y-1 min-w-0">
        <div className="flex items-center gap-2 flex-wrap">
          <Badge variant={ACTION_VARIANT[entry.action]} className="text-xs capitalize">
            {entry.action}
          </Badge>
          <span className="text-xs text-muted-foreground font-mono">{entry.entityType}</span>
          {entry.entityName && (
            <span className="text-xs font-medium truncate">{entry.entityName}</span>
          )}
        </div>
        {entry.changes && entry.changes.length > 0 && (
          <div className="space-y-0.5 pl-1">
            {entry.changes.map((c) => (
              <ChangeRow key={c.field} field={c.field} from={c.from} to={c.to} />
            ))}
          </div>
        )}
        <p className="text-xs text-muted-foreground">
          {entry.actorEmail} · {new Date(entry.occurredAt).toLocaleString()}
        </p>
      </div>
    </div>
  )
}

interface TodoHistoryDialogProps {
  todoId: number | null
  todoName: string | null
  onClose: () => void
}

export function TodoHistoryDialog({ todoId, todoName, onClose }: TodoHistoryDialogProps) {
  const { data, isLoading, isError } = useAdminTodoHistory(todoId)

  return (
    <Dialog open={todoId !== null} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-lg max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <History className="h-4 w-4" />
            History{todoName ? ` — ${todoName}` : ''}
          </DialogTitle>
        </DialogHeader>

        <div className="flex-1 overflow-y-auto pr-1">
          {isLoading && (
            <div className="space-y-3 py-2">
              {Array.from({ length: 4 }).map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
              ))}
            </div>
          )}

          {isError && (
            <p className="py-6 text-center text-sm text-destructive">Failed to load history.</p>
          )}

          {data?.length === 0 && (
            <p className="py-6 text-center text-sm text-muted-foreground">No history yet.</p>
          )}

          {data && data.length > 0 && (
            <div>
              {data.map((entry) => (
                <EntryRow key={entry.id} entry={entry} />
              ))}
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  )
}
