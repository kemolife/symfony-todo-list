import { useTodoTags } from '../../api/useTodos'
import { Button } from '../../components/Button'
import { Input } from '../../components/Input'
import { Select } from '../../components/Select'
import { useTodoFilterStore } from '../../store/todoFilterStore'
import type { TodoStatus } from '../../types/todo'

export function TodoFilters() {
  const { filters, setFilter, clearFilters } = useTodoFilterStore()
  const { data: tags = [] } = useTodoTags()
  const hasFilters = filters.status !== undefined || filters.tag !== undefined || filters.search !== undefined

  return (
    <div className="flex flex-wrap gap-3">
      <Input
        placeholder="Search..."
        value={filters.search ?? ''}
        onChange={(e) => setFilter('search', e.target.value || undefined)}
        className="max-w-xs"
      />

      <Select
        value={filters.status ?? ''}
        onChange={(e) => setFilter('status', (e.target.value as TodoStatus) || undefined)}
        placeholder="All statuses"
      >
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="done">Done</option>
      </Select>

      <Select
        value={filters.tag ?? ''}
        onChange={(e) => setFilter('tag', e.target.value || undefined)}
        placeholder="All tags"
      >
        {tags.map((tag) => (
          <option key={tag} value={tag}>{tag}</option>
        ))}
      </Select>

      {hasFilters && (
        <Button variant="secondary" size="sm" onClick={clearFilters}>
          Clear filters
        </Button>
      )}
    </div>
  )
}
