import { create } from 'zustand'

interface ModalState {
  editingTodoId: number | null
  isCreateOpen: boolean
  openCreate: () => void
  openEdit: (id: number) => void
  close: () => void
}

export const useModalStore = create<ModalState>((set) => ({
  editingTodoId: null,
  isCreateOpen: false,
  openCreate: () => set({ isCreateOpen: true, editingTodoId: null }),
  openEdit: (id) => set({ editingTodoId: id, isCreateOpen: false }),
  close: () => set({ isCreateOpen: false, editingTodoId: null }),
}))
