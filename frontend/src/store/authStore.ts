import { create } from 'zustand'
import { persist } from 'zustand/middleware'

function decodeJwtRoles(token: string): string[] {
  try {
    const segment = token.split('.')[1]
    if (!segment) return []
    const payload = JSON.parse(atob(segment))
    return Array.isArray(payload.roles) ? payload.roles : []
  } catch {
    return []
  }
}

interface AuthState {
  token: string | null
  preAuthToken: string | null
  isAuthenticated: boolean
  roles: string[]
  isAdmin: () => boolean
  setToken: (token: string) => void
  clearToken: () => void
  setPreAuthToken: (preAuthToken: string) => void
  clearPreAuthToken: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      preAuthToken: null,
      isAuthenticated: false,
      roles: [],
      isAdmin: () => get().roles.includes('ROLE_ADMIN'),
      setToken: (token) =>
        set({ token, isAuthenticated: true, preAuthToken: null, roles: decodeJwtRoles(token) }),
      clearToken: () => set({ token: null, isAuthenticated: false, roles: [] }),
      setPreAuthToken: (preAuthToken) => set({ preAuthToken }),
      clearPreAuthToken: () => set({ preAuthToken: null }),
    }),
    { name: 'auth' },
  ),
)
