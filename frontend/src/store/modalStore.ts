import { create } from 'zustand'

type ModalState =
  | { mode: 'closed' }
  | { mode: 'create' }
  | { mode: 'edit'; id: number }

interface ModalStore {
  modal: ModalState
  openCreate: () => void
  openEdit: (id: number) => void
  close: () => void
}

export const useModalStore = create<ModalStore>((set) => ({
  modal: { mode: 'closed' },
  openCreate: () => set({ modal: { mode: 'create' } }),
  openEdit: (id) => set({ modal: { mode: 'edit', id } }),
  close: () => set({ modal: { mode: 'closed' } }),
}))
