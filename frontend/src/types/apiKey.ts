export type ApiKeyPermission = 'read' | 'write' | 'delete'

export interface ApiKeyEntry {
  id: number
  name: string
  description: string | null
  permissions: ApiKeyPermission[]
  createdAt: string
  lastUsedAt: string | null
  prefix: string
  keyValue: string | null
}

export interface CreateApiKeyData {
  name: string
  description?: string
  permissions: ApiKeyPermission[]
}
