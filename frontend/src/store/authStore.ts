import { create } from 'zustand'
import { persist } from 'zustand/middleware'

function decodeJwt(token: string): { roles: string[]; twoFactorConfirmed: boolean; email: string } {
  const segment = token.split('.')[1]
  if (!segment) throw new Error('Invalid JWT: missing payload segment')
  const payload = JSON.parse(atob(segment)) as Record<string, unknown>
  return {
    roles: Array.isArray(payload.roles) ? (payload.roles as string[]) : [],
    twoFactorConfirmed: payload.twoFactorConfirmed === true,
    email: typeof payload.username === 'string' ? payload.username : '',
  }
}

interface AuthState {
  token: string | null
  preAuthToken: string | null
  isAuthenticated: boolean
  roles: string[]
  email: string
  needsTwoFactorSetup: boolean
  isAdmin: () => boolean
  setToken: (token: string) => void
  setTokenAndCheckSetup: (token: string) => void
  clearToken: () => void
  logout: () => void
  setPreAuthToken: (preAuthToken: string) => void
  clearPreAuthToken: () => void
  clearTwoFactorSetupPending: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      preAuthToken: null,
      isAuthenticated: false,
      roles: [],
      email: '',
      needsTwoFactorSetup: false,
      isAdmin: () => get().roles.includes('ROLE_ADMIN'),
      setToken: (token) => {
        try {
          const { roles, email } = decodeJwt(token)
          set({ token, isAuthenticated: true, preAuthToken: null, roles, email, needsTwoFactorSetup: false })
        } catch {
          set({ token: null, isAuthenticated: false, roles: [], email: '', needsTwoFactorSetup: false })
        }
      },
      setTokenAndCheckSetup: (token) => {
        try {
          const { roles, twoFactorConfirmed, email } = decodeJwt(token)
          const needsSetup = roles.includes('ROLE_ADMIN') && !twoFactorConfirmed
          set({ token, isAuthenticated: true, preAuthToken: null, roles, email, needsTwoFactorSetup: needsSetup })
        } catch {
          set({ token: null, isAuthenticated: false, roles: [], email: '', needsTwoFactorSetup: false })
        }
      },
      clearToken: () => set({ token: null, isAuthenticated: false, roles: [], email: '', needsTwoFactorSetup: false }),
      logout: () => {
        set({ token: null, isAuthenticated: false, roles: [], email: '', needsTwoFactorSetup: false })
        window.location.href = '/login'
      },
      setPreAuthToken: (preAuthToken) => set({ preAuthToken }),
      clearPreAuthToken: () => set({ preAuthToken: null }),
      clearTwoFactorSetupPending: () => set({ needsTwoFactorSetup: false }),
    }),
    { name: 'auth' },
  ),
)
