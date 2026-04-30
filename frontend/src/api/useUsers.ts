import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/axios'
import { useAuthStore } from '../store/authStore'
import type { User, UserRole } from '../types/user'

interface CreateUserPayload {
  email: string
  password: string
  role: UserRole
}

interface UpdateUserPayload {
  email: string
  password: string
  role?: UserRole
}

const USERS_KEY = ['users'] as const

export function useUsers() {
  return useQuery({
    queryKey: USERS_KEY,
    queryFn: async () => {
      const { data } = await api.get<User[]>('/api/users/')
      return data
    },
  })
}

export function useUserSearch(search: string) {
  return useQuery({
    queryKey: [...USERS_KEY, 'search', search],
    queryFn: async () => {
      const { data } = await api.get<User[]>('/api/users/', { params: { search } })
      return data
    },
    enabled: search.length >= 2,
  })
}

export function useCreateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (payload: CreateUserPayload) => {
      await api.post('/api/users/', payload)
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: USERS_KEY }),
  })
}

export function useUpdateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, ...payload }: UpdateUserPayload & { id: number }) => {
      const { data } = await api.put<User>(`/api/users/${id}`, payload)
      return data
    },
    onSuccess: (updatedUser) => {
      qc.invalidateQueries({ queryKey: USERS_KEY })
      const { email, logout } = useAuthStore.getState()
      if (updatedUser.email === email && !updatedUser.roles.includes('ROLE_ADMIN')) {
        logout()
      }
    },
  })
}

export function useDeleteUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/users/${id}`)
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: USERS_KEY }),
  })
}

