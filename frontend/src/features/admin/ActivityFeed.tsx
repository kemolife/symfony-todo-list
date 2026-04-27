import { useState } from 'react'
import { ChevronDown, ChevronRight } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { useActivityFeed, type ActivityAction } from '@/api/useDashboard'
import type { AuditLogEntry } from '@/api/useAdminTodos'

const ACTION_VARIANT: Record<AuditLogEntry['action'], 'default' | 'secondary' | 'destructive'> = {
  created: 'default',
  updated: 'secondary',
  deleted: 'destructive',
}

const FILTER_OPTIONS: Array<{ label: string; value: ActivityAction | undefined }> = [
  { label: 'All', value: undefined },
  { label: 'Created', value: 'created' },
  { label: 'Updated', value: 'updated' },
  { label: 'Deleted', value: 'deleted' },
]

function formatRelativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const minutes = Math.floor(diff / 60_000)
  if (minutes < 1) return 'just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days}d ago`
  return new Date(iso).toLocaleDateString()
}

function formatEntityType(type: string): string {
  return type.replace('_', ' ')
}

function ChangeRow({ field, from, to }: { field: string; from: unknown; to: unknown }) {
  const fmt = (v: unknown) => (v === null || v === undefined ? '—' : String(v))
  return (
    <div className="flex items-center gap-2 text-xs">
      <span className="w-24 shrink-0 font-mono text-muted-foreground">{field}</span>
      <span className="text-muted-foreground line-through">{fmt(from)}</span>
      <span className="text-xs text-muted-foreground">→</span>
      <span className="text-foreground">{fmt(to)}</span>
    </div>
  )
}

function ActivityEntry({ entry }: { entry: AuditLogEntry }) {
  const [expanded, setExpanded] = useState(false)
  const hasChanges = entry.changes && entry.changes.length > 0

  return (
    <div className="flex gap-3 py-3 border-b last:border-0">
      <div className="flex flex-col items-center pt-1">
        <div className="h-2 w-2 rounded-full bg-border mt-0.5 shrink-0" />
      </div>
      <div className="flex-1 space-y-1 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <div className="flex items-center gap-2 flex-wrap">
            <Badge variant={ACTION_VARIANT[entry.action]} className="text-xs capitalize">
              {entry.action}
            </Badge>
            <span className="text-xs text-muted-foreground">{formatEntityType(entry.entityType)}</span>
            {entry.entityName && (
              <span className="text-xs font-medium truncate max-w-[160px]" title={entry.entityName}>
                "{entry.entityName}"
              </span>
            )}
          </div>
          <span className="text-xs text-muted-foreground shrink-0">{formatRelativeTime(entry.occurredAt)}</span>
        </div>

        <p className="text-xs text-muted-foreground">{entry.actorEmail}</p>

        {hasChanges && (
          <div>
            <button
              onClick={() => setExpanded((v) => !v)}
              className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
            >
              {expanded ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
              {entry.changes!.length} change{entry.changes!.length !== 1 ? 's' : ''}
            </button>

            {expanded && (
              <div className="mt-1 space-y-0.5 pl-4 border-l">
                {entry.changes!.map((c) => (
                  <ChangeRow key={c.field} field={c.field} from={c.from} to={c.to} />
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  )
}

export function ActivityFeed() {
  const [action, setAction] = useState<ActivityAction | undefined>(undefined)
  const { data, isLoading, isError } = useActivityFeed(action)

  return (
    <Card>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <CardTitle className="text-base">Recent Activity</CardTitle>
          <div className="flex gap-1">
            {FILTER_OPTIONS.map(({ label, value }) => (
              <Button
                key={label}
                variant={action === value ? 'default' : 'outline'}
                size="sm"
                className="h-7 px-2.5 text-xs"
                onClick={() => setAction(value)}
              >
                {label}
              </Button>
            ))}
          </div>
        </div>
      </CardHeader>

      <CardContent className="pt-0">
        {isLoading && (
          <div className="space-y-3">
            {Array.from({ length: 5 }).map((_, i) => (
              <Skeleton key={i} className="h-12 w-full" />
            ))}
          </div>
        )}

        {isError && (
          <p className="py-4 text-center text-sm text-destructive">Failed to load activity.</p>
        )}

        {data?.length === 0 && (
          <p className="py-4 text-center text-sm text-muted-foreground">No activity yet.</p>
        )}

        {data && data.length > 0 && (
          <div>
            {data.map((entry) => (
              <ActivityEntry key={entry.id} entry={entry} />
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
