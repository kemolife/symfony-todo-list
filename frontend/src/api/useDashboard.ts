import { useQuery } from '@tanstack/react-query'
import { api } from '../lib/axios'

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
