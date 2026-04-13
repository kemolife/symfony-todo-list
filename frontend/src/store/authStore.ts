import { create } from 'zustand'
import { persist } from 'zustand/middleware'

function decodeJwt(token: string): { roles: string[]; twoFactorConfirmed: boolean; email: string } {
  try {
    const segment = token.split('.')[1]
    if (!segment) return { roles: [], twoFactorConfirmed: false, email: '' }
    const payload = JSON.parse(atob(segment))
    return {
      roles: Array.isArray(payload.roles) ? payload.roles : [],
      twoFactorConfirmed: payload.twoFactorConfirmed === true,
      email: typeof payload.username === 'string' ? payload.username : '',
    }
  } catch {
    return { roles: [], twoFactorConfirmed: false, email: '' }
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
        const { roles, email } = decodeJwt(token)
        set({ token, isAuthenticated: true, preAuthToken: null, roles, email, needsTwoFactorSetup: false })
      },
      setTokenAndCheckSetup: (token) => {
        const { roles, twoFactorConfirmed, email } = decodeJwt(token)
        const needsSetup = roles.includes('ROLE_ADMIN') && !twoFactorConfirmed
        set({ token, isAuthenticated: true, preAuthToken: null, roles, email, needsTwoFactorSetup: needsSetup })
      },
      clearToken: () => set({ token: null, isAuthenticated: false, roles: [], email: '', needsTwoFactorSetup: false }),
      setPreAuthToken: (preAuthToken) => set({ preAuthToken }),
      clearPreAuthToken: () => set({ preAuthToken: null }),
      clearTwoFactorSetupPending: () => set({ needsTwoFactorSetup: false }),
    }),
    { name: 'auth' },
  ),
)
