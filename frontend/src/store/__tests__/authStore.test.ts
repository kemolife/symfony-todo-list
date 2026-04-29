import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from '../authStore'

const makeToken = (payload: object) =>
  `eyJhbGciOiJIUzI1NiJ9.${btoa(JSON.stringify(payload))}.sig`

beforeEach(() => {
  localStorage.clear()
  useAuthStore.setState({
    token: null,
    preAuthToken: null,
    isAuthenticated: false,
    roles: [],
    email: '',
    needsTwoFactorSetup: false,
  })
})

describe('authStore', () => {
  it('setToken decodes JWT roles and email', () => {
    const token = makeToken({ roles: ['ROLE_USER'], twoFactorConfirmed: false, username: 'user@example.com' })
    useAuthStore.getState().setToken(token)
    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(true)
    expect(state.roles).toEqual(['ROLE_USER'])
    expect(state.email).toBe('user@example.com')
    expect(state.needsTwoFactorSetup).toBe(false)
  })

  it('setTokenAndCheckSetup flags needsTwoFactorSetup for admin without 2FA', () => {
    const token = makeToken({ roles: ['ROLE_ADMIN'], twoFactorConfirmed: false, username: 'admin@example.com' })
    useAuthStore.getState().setTokenAndCheckSetup(token)
    expect(useAuthStore.getState().needsTwoFactorSetup).toBe(true)
  })

  it('setTokenAndCheckSetup does not flag setup when 2FA already confirmed', () => {
    const token = makeToken({ roles: ['ROLE_ADMIN'], twoFactorConfirmed: true, username: 'admin@example.com' })
    useAuthStore.getState().setTokenAndCheckSetup(token)
    expect(useAuthStore.getState().needsTwoFactorSetup).toBe(false)
  })

  it('clearToken resets all auth state', () => {
    const token = makeToken({ roles: ['ROLE_USER'], twoFactorConfirmed: false, username: 'user@example.com' })
    useAuthStore.getState().setToken(token)
    useAuthStore.getState().clearToken()
    const state = useAuthStore.getState()
    expect(state.token).toBeNull()
    expect(state.isAuthenticated).toBe(false)
    expect(state.roles).toEqual([])
    expect(state.email).toBe('')
  })

  it('isAdmin returns true when ROLE_ADMIN in roles', () => {
    useAuthStore.setState({ roles: ['ROLE_ADMIN'] })
    expect(useAuthStore.getState().isAdmin()).toBe(true)
  })

  it('isAdmin returns false for regular user', () => {
    useAuthStore.setState({ roles: ['ROLE_USER'] })
    expect(useAuthStore.getState().isAdmin()).toBe(false)
  })

  it('setPreAuthToken stores pre-auth token', () => {
    useAuthStore.getState().setPreAuthToken('pre-token-123')
    expect(useAuthStore.getState().preAuthToken).toBe('pre-token-123')
  })
})
