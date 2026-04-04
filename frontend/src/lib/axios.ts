import axios from 'axios'

export const api = axios.create({
  baseURL: import.meta.env['VITE_API_URL'] ?? 'http://localhost:8000',
  headers: { 'Content-Type': 'application/json' },
})

api.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error)) {
      const message = (error.response?.data as { error?: string } | undefined)?.error
        ?? error.message
        ?? 'Unknown error'
      return Promise.reject(new Error(message))
    }
    return Promise.reject(error)
  },
)
