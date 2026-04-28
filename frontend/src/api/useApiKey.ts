import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/axios'
import type { ApiKeyEntry, CreateApiKeyData } from '../types/apiKey'

export interface ProfileData {
  id: number
  email: string
  name: string | null
  apiKeyCount: number
  roles: string[]
}

const PROFILE_KEY = ['profile'] as const
const API_KEYS_KEY = ['api-keys'] as const
const ADMIN_API_KEYS_KEY = ['admin-api-keys'] as const

export function useProfile() {
  return useQuery({
    queryKey: PROFILE_KEY,
    queryFn: async (): Promise<ProfileData> => {
      const { data } = await api.get<ProfileData>('/api/profile')
      return data
    },
  })
}

export function useApiKeys() {
  return useQuery({
    queryKey: API_KEYS_KEY,
    queryFn: async (): Promise<ApiKeyEntry[]> => {
      const { data } = await api.get<ApiKeyEntry[]>('/api/profile/api-keys')
      return data
    },
  })
}

export function useCreateApiKey() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (payload: CreateApiKeyData): Promise<ApiKeyEntry> => {
      const { data } = await api.post<ApiKeyEntry>('/api/profile/api-keys', payload)
      return data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: API_KEYS_KEY })
      qc.invalidateQueries({ queryKey: PROFILE_KEY })
    },
  })
}

export function useRevokeApiKey() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number): Promise<void> => {
      await api.delete(`/api/profile/api-keys/${id}`)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: API_KEYS_KEY })
      qc.invalidateQueries({ queryKey: PROFILE_KEY })
    },
  })
}

export function useAdminUserApiKeys(userId: number | null) {
  return useQuery({
    queryKey: [...ADMIN_API_KEYS_KEY, userId],
    queryFn: async (): Promise<ApiKeyEntry[]> => {
      const { data } = await api.get<ApiKeyEntry[]>(`/api/admin/users/${userId}/api-keys`)
      return data
    },
    enabled: userId !== null,
  })
}

export function useAdminRevokeApiKey() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number): Promise<void> => {
      await api.delete(`/api/admin/api-keys/${id}`)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_API_KEYS_KEY })
    },
  })
}

export function useUpdateProfile() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (payload: { name: string | null }): Promise<ProfileData> => {
      const { data } = await api.patch<ProfileData>('/api/profile', payload)
      return data
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: PROFILE_KEY }),
  })
}

export function useChangePassword() {
  return useMutation({
    mutationFn: async (payload: { currentPassword: string; newPassword: string }): Promise<void> => {
      await api.patch('/api/profile/password', payload)
    },
  })
}
