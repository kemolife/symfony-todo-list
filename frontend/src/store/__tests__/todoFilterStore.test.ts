import { describe, it, expect, beforeEach } from 'vitest'
import { useTodoFilterStore } from '../todoFilterStore'

beforeEach(() => {
  useTodoFilterStore.setState({ filters: { page: 1 } })
})

describe('todoFilterStore', () => {
  it('setFilter updates the given filter', () => {
    useTodoFilterStore.getState().setFilter('status', 'done')
    expect(useTodoFilterStore.getState().filters.status).toBe('done')
  })

  it('setFilter resets page to 1 when non-page filter changes', () => {
    useTodoFilterStore.setState({ filters: { page: 3, status: 'pending' } })
    useTodoFilterStore.getState().setFilter('status', 'done')
    expect(useTodoFilterStore.getState().filters.page).toBe(1)
  })

  it('setFilter preserves other filters when one changes', () => {
    useTodoFilterStore.setState({ filters: { page: 1, status: 'pending', tag: 'work' } })
    useTodoFilterStore.getState().setFilter('status', 'done')
    expect(useTodoFilterStore.getState().filters.tag).toBe('work')
  })

  it('setPage changes page without resetting filters', () => {
    useTodoFilterStore.setState({ filters: { page: 1, status: 'pending' } })
    useTodoFilterStore.getState().setPage(5)
    const state = useTodoFilterStore.getState()
    expect(state.filters.page).toBe(5)
    expect(state.filters.status).toBe('pending')
  })

  it('clearFilters resets to default state', () => {
    useTodoFilterStore.setState({ filters: { page: 5, status: 'done', tag: 'work', search: 'test' } })
    useTodoFilterStore.getState().clearFilters()
    expect(useTodoFilterStore.getState().filters).toEqual({ page: 1 })
  })
})
