export interface User {
  id: number
  email: string
  roles: string[]
  hasTwoFactor: boolean
}

export type UserRole = 'admin' | 'user'
