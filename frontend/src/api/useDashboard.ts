import { useQuery } from '@tanstack/react-query'
import { api } from '../lib/axios'
import type { AuditLogEntry } from './useAdminTodos'

interface DashboardData {
  message: string
}

export function useDashboard() {
  return useQuery({
    queryKey: ['dashboard'],
    queryFn: async () => {
      const { data } = await api.get<DashboardData>('/api/dashboard/')
      return data
    },
  })
}

export type ActivityAction = 'created' | 'updated' | 'deleted'

export function useActivityFeed(action?: ActivityAction) {
  return useQuery({
    queryKey: ['dashboard-activity', action],
    queryFn: async () => {
      const params: Record<string, string> = {}
      if (action) params['action'] = action
      const { data } = await api.get<AuditLogEntry[]>('/api/dashboard/activity', { params })
      return data
    },
  })
}
