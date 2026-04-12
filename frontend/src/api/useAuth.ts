import { useMutation } from '@tanstack/react-query'
import { api } from '../lib/axios'
import { useAuthStore } from '../store/authStore'

interface AuthCredentials {
  email: string
  password: string
  password_confirmation?: string
}

interface AuthResponse {
  token: string
}

interface LoginResponse {
  token?: string
  two_factor_required?: boolean
  pre_auth_token?: string
}

interface AdminRegisterCredentials {
  email: string
  password: string
  password_confirmation: string
  admin_secret: string
}

export interface AdminRegisterResponse {
  token: string
  totp_secret: string
  totp_uri: string
}

export function useRegister() {
  const setToken = useAuthStore((s) => s.setToken)
  return useMutation({
    mutationFn: async (credentials: AuthCredentials) => {
      const { data } = await api.post<AuthResponse>('/api/auth/register', credentials)
      return data
    },
    onSuccess: ({ token }) => setToken(token),
  })
}

export function useLogin() {
  const setToken = useAuthStore((s) => s.setToken)
  const setPreAuthToken = useAuthStore((s) => s.setPreAuthToken)
  return useMutation({
    mutationFn: async (credentials: AuthCredentials) => {
      const { data } = await api.post<LoginResponse>('/api/auth/login', credentials)
      return data
    },
    onSuccess: (data) => {
      if (data.two_factor_required && data.pre_auth_token) {
        setPreAuthToken(data.pre_auth_token)
      } else if (data.token) {
        setToken(data.token)
      }
    },
  })
}

export function useVerify2fa() {
  const setToken = useAuthStore((s) => s.setToken)
  return useMutation({
    mutationFn: async (payload: { pre_auth_token: string; code: string }) => {
      const { data } = await api.post<AuthResponse>('/api/auth/2fa/check', payload)
      return data
    },
    onSuccess: ({ token }) => setToken(token),
  })
}

export function useAdminRegister() {
  return useMutation({
    mutationFn: async (credentials: AdminRegisterCredentials) => {
      const { data } = await api.post<AdminRegisterResponse>('/api/admin/register', credentials)
      return data
    },
  })
}
