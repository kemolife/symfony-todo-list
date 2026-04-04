import { create } from 'zustand'
import type { TodoFilters } from '../types/todo'

interface TodoFilterState {
  filters: TodoFilters
  setFilter: <K extends keyof TodoFilters>(key: K, value: TodoFilters[K]) => void
  clearFilters: () => void
}

export const useTodoFilterStore = create<TodoFilterState>((set) => ({
  filters: {},
  setFilter: (key, value) =>
    set((state) => ({ filters: { ...state.filters, [key]: value } })),
  clearFilters: () => set({ filters: {} }),
}))
