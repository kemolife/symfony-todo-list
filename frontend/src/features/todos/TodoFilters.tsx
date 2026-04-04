import { useTodoTags } from '@/api/useTodos'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { useTodoFilterStore } from '@/store/todoFilterStore'
import type { TodoStatus } from '@/types/todo'
import { X, Search } from 'lucide-react'

export function TodoFilters() {
  const { filters, setFilter, clearFilters } = useTodoFilterStore()
  const { data: tags = [] } = useTodoTags()
  const hasFilters = filters.status !== undefined || filters.tag !== undefined || filters.search !== undefined

  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="relative flex-1 min-w-[160px] max-w-xs">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search todos..."
          value={filters.search ?? ''}
          onChange={(e) => setFilter('search', e.target.value || undefined)}
          className="pl-8"
        />
      </div>

      <Select
        value={filters.status ?? ''}
        onValueChange={(v) => setFilter('status', (v as TodoStatus) || undefined)}
      >
        <SelectTrigger className="w-36">
          <SelectValue placeholder="All statuses" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="pending">Pending</SelectItem>
          <SelectItem value="in_progress">In Progress</SelectItem>
          <SelectItem value="done">Done</SelectItem>
        </SelectContent>
      </Select>

      {tags.length > 0 && (
        <Select
          value={filters.tag ?? ''}
          onValueChange={(v) => setFilter('tag', v || undefined)}
        >
          <SelectTrigger className="w-32">
            <SelectValue placeholder="All tags" />
          </SelectTrigger>
          <SelectContent>
            {tags.map((tag) => (
              <SelectItem key={tag} value={tag}>{tag}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      )}

      {hasFilters && (
        <Button variant="ghost" size="sm" onClick={clearFilters} className="gap-1.5 text-muted-foreground">
          <X className="h-3.5 w-3.5" />
          Clear
        </Button>
      )}
    </div>
  )
}
