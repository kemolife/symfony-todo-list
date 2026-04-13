import axios from 'axios'
import { useAuthStore } from '../store/authStore'

export const api = axios.create({
  baseURL: import.meta.env['VITE_API_URL'] ?? 'http://localhost:8000',
  headers: { 'Content-Type': 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error)) {
      if (error.response?.status === 401 || error.response?.status === 403) {
        useAuthStore.getState().clearToken()
        window.location.href = '/login'
      }
      const message = (error.response?.data as { error?: string } | undefined)?.error
        ?? error.message
        ?? 'Unknown error'
      return Promise.reject(new Error(message))
    }
    return Promise.reject(error)
  },
)
