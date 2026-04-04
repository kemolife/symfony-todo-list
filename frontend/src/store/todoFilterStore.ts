import { create } from 'zustand'
import type { TodoFilters } from '../types/todo'

interface TodoFilterState {
  filters: TodoFilters
  setFilter: <K extends keyof TodoFilters>(key: K, value: TodoFilters[K]) => void
  setPage: (page: number) => void
  clearFilters: () => void
}

export const useTodoFilterStore = create<TodoFilterState>((set) => ({
  filters: { page: 1 },
  setFilter: (key, value) =>
    set((state) => ({
      filters: {
        ...state.filters,
        [key]: value,
        // Reset to page 1 when any non-page filter changes
        ...(key !== 'page' && key !== 'limit' ? { page: 1 } : {}),
      },
    })),
  setPage: (page) =>
    set((state) => ({ filters: { ...state.filters, page } })),
  clearFilters: () => set({ filters: { page: 1 } }),
}))
