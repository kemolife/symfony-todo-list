export interface User {
  id: number
  email: string
  roles: string[]
  hasTwoFactor: boolean
  apiKeyCount: number
}

export type UserRole = 'admin' | 'user'
