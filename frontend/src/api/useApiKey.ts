import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/axios'

interface Profile {
  hasApiKey: boolean
}

interface ApiKeyResponse {
  apiKey: string
}

const PROFILE_KEY = ['profile'] as const

export function useProfile() {
  return useQuery({
    queryKey: PROFILE_KEY,
    queryFn: async (): Promise<Profile> => {
      const { data } = await api.get<Profile>('/api/profile')
      return data
    },
  })
}

export function useGenerateApiKey() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (): Promise<ApiKeyResponse> => {
      const { data } = await api.post<ApiKeyResponse>('/api/profile/api-key')
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: PROFILE_KEY }),
  })
}

export function useRevokeApiKey() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (): Promise<void> => {
      await api.delete('/api/profile/api-key')
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: PROFILE_KEY }),
  })
}
