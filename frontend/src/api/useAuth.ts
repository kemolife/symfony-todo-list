import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
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

interface TwoFactorSetupData {
  totp_uri: string
  totp_secret: string
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
  const setPreAuthToken = useAuthStore((s) => s.setPreAuthToken)
  const setTokenAndCheckSetup = useAuthStore((s) => s.setTokenAndCheckSetup)
  return useMutation({
    mutationFn: async (credentials: AuthCredentials) => {
      const { data } = await api.post<LoginResponse>('/api/auth/login', credentials)
      return data
    },
    onSuccess: (data) => {
      if (data.two_factor_required && data.pre_auth_token) {
        setPreAuthToken(data.pre_auth_token)
      } else if (data.token) {
        setTokenAndCheckSetup(data.token)
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

export function useSetup2fa() {
  return useQuery({
    queryKey: ['2fa-setup'],
    queryFn: async () => {
      const { data } = await api.get<TwoFactorSetupData>('/api/auth/2fa/setup')
      return data
    },
    retry: false,
    staleTime: 0,
    gcTime: 0,
  })
}

export function useConfirm2fa() {
  const clearTwoFactorSetupPending = useAuthStore((s) => s.clearTwoFactorSetupPending)
  return useMutation({
    mutationFn: async (payload: { code: string }) => {
      await api.post('/api/auth/2fa/confirm', payload)
    },
    onSuccess: () => clearTwoFactorSetupPending(),
  })
}

export function useGetEnrollment(token: string) {
  return useQuery({
    queryKey: ['enrollment', token],
    queryFn: async () => {
      const { data } = await api.get<{ totp_uri: string; totp_secret: string }>(
        `/api/auth/2fa/enroll/${token}`,
      )
      return data
    },
    enabled: token.length > 0,
    retry: false,
    staleTime: 0,
    gcTime: 0,
  })
}

export function useConfirmEnrollment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ token, code }: { token: string; code: string }) => {
      await api.post(`/api/auth/2fa/enroll/${token}`, { code })
    },
    onSuccess: (_, { token }) => {
      qc.removeQueries({ queryKey: ['enrollment', token] })
    },
  })
}
