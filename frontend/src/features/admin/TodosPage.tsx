import { useEffect, useRef, useState } from 'react'
import { ChevronLeft, ChevronRight, X } from 'lucide-react'
import { useAdminTodos } from '@/api/useAdminTodos'
import { useUserSearch } from '@/api/useUsers'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { TodoStatus } from '@/types/todo'

function useDebounce(value: string, delay: number) {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(t)
  }, [value, delay])
  return debounced
}

interface UserSearchProps {
  onSelect: (userId: number | undefined) => void
}

function UserSearch({ onSelect }: UserSearchProps) {
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)
  const [selectedEmail, setSelectedEmail] = useState<string | null>(null)
  const debouncedQuery = useDebounce(query, 300)
  const containerRef = useRef<HTMLDivElement>(null)

  const { data: results } = useUserSearch(debouncedQuery)

  const handleSelect = (userId: number, email: string) => {
    setSelectedEmail(email)
    setQuery(email)
    setOpen(false)
    onSelect(userId)
  }

  const handleClear = () => {
    setSelectedEmail(null)
    setQuery('')
    onSelect(undefined)
  }

  const showDropdown = open && !selectedEmail && debouncedQuery.length >= 2

  return (
    <div ref={containerRef} className="relative w-56">
      <div className="relative">
        <Input
          value={query}
          onChange={(e) => {
            setQuery(e.target.value)
            if (selectedEmail) {
              setSelectedEmail(null)
              onSelect(undefined)
            }
          }}
          onFocus={() => setOpen(true)}
          onBlur={() => setTimeout(() => setOpen(false), 150)}
          placeholder="Search user by email…"
          className="pr-7"
        />
        {selectedEmail && (
          <button
            type="button"
            onClick={handleClear}
            className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
          >
            <X className="h-3.5 w-3.5" />
          </button>
        )}
      </div>

      {showDropdown && (
        <div className="absolute z-50 mt-1 w-full rounded-md border bg-background shadow-md">
          {results && results.length > 0 ? (
            results.map((user) => (
              <button
                key={user.id}
                type="button"
                onMouseDown={() => handleSelect(user.id, user.email)}
                className="w-full px-3 py-2 text-left text-sm hover:bg-muted"
              >
                {user.email}
              </button>
            ))
          ) : (
            <p className="px-3 py-2 text-sm text-muted-foreground">No users found.</p>
          )}
        </div>
      )}
    </div>
  )
}

const STATUS_LABELS: Record<TodoStatus, string> = {
  pending: 'Pending',
  in_progress: 'In Progress',
  done: 'Done',
}

const STATUS_VARIANTS: Record<TodoStatus, 'secondary' | 'outline' | 'default'> = {
  pending: 'secondary',
  in_progress: 'outline',
  done: 'default',
}

const LIMIT = 15

export function TodosPage() {
  const [userId, setUserId] = useState<number | undefined>(undefined)
  const [status, setStatus] = useState<TodoStatus | undefined>(undefined)
  const [page, setPage] = useState(1)

  const { data, isLoading, isError } = useAdminTodos({ userId, status, page, limit: LIMIT })

  const handleStatusChange = (value: string | null) => {
    setStatus(!value || value === 'all' ? undefined : (value as TodoStatus))
    setPage(1)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">All Todos</h2>
        {data && (
          <span className="text-sm text-muted-foreground">{data.total} total</span>
        )}
      </div>

      <div className="flex gap-3">
        <UserSearch onSelect={(id) => { setUserId(id); setPage(1) }} />

        <Select onValueChange={handleStatusChange} defaultValue="all">
          <SelectTrigger className="w-40">
            <SelectValue placeholder="All statuses" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="in_progress">In Progress</SelectItem>
            <SelectItem value="done">Done</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="rounded-lg border bg-background">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50">
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">User</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Tag</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Created</th>
            </tr>
          </thead>
          <tbody>
            {isLoading &&
              Array.from({ length: 8 }).map((_, i) => (
                <tr key={i} className="border-b last:border-0">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-36" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-48" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-16" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-20" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-24" /></td>
                </tr>
              ))}

            {isError && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-sm text-destructive">
                  Failed to load todos.
                </td>
              </tr>
            )}

            {!isLoading && data?.items.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-sm text-muted-foreground">
                  No todos found.
                </td>
              </tr>
            )}

            {data?.items.map((todo) => (
              <tr key={todo.id} className="border-b last:border-0 hover:bg-muted/30">
                <td className="px-4 py-3 text-muted-foreground">{todo.ownerEmail ?? '—'}</td>
                <td className="px-4 py-3 font-medium">{todo.name}</td>
                <td className="px-4 py-3">
                  {todo.tag ? (
                    <Badge variant="outline" className="text-xs">{todo.tag}</Badge>
                  ) : (
                    <span className="text-muted-foreground">—</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  <Badge variant={STATUS_VARIANTS[todo.status]} className="text-xs">
                    {STATUS_LABELS[todo.status]}
                  </Badge>
                </td>
                <td className="px-4 py-3 text-muted-foreground">
                  {new Date(todo.createdAt).toLocaleDateString()}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {data && data.pages > 1 && (
        <div className="flex items-center justify-end gap-2">
          <span className="text-sm text-muted-foreground">
            Page {data.page} of {data.pages}
          </span>
          <Button
            variant="outline"
            size="icon"
            className="h-8 w-8"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            className="h-8 w-8"
            disabled={page >= data.pages}
            onClick={() => setPage((p) => p + 1)}
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}
    </div>
  )
}
