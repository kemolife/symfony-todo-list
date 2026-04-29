import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter } from 'react-router-dom'
import { describe, it, expect, beforeEach } from 'vitest'
import { LoginPage } from '../LoginPage'
import { useAuthStore } from '../../../store/authStore'

function renderLogin() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={client}>
      <MemoryRouter>
        <LoginPage />
      </MemoryRouter>
    </QueryClientProvider>,
  )
}

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

describe('LoginPage', () => {
  it('renders email and password fields', () => {
    renderLogin()
    expect(screen.getByLabelText('Email')).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
  })

  it('renders sign in button', () => {
    renderLogin()
    expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
  })

  it('renders register link', () => {
    renderLogin()
    expect(screen.getByRole('link', { name: /register/i })).toBeInTheDocument()
  })

  it('shows validation error when email is missing', async () => {
    const user = userEvent.setup()
    renderLogin()
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in/i }))
    expect(await screen.findByText('Enter a valid email')).toBeInTheDocument()
  })

  it('shows validation error for empty password', async () => {
    const user = userEvent.setup()
    renderLogin()
    await user.type(screen.getByLabelText('Email'), 'user@example.com')
    await user.click(screen.getByRole('button', { name: /sign in/i }))
    expect(await screen.findByText('Password is required')).toBeInTheDocument()
  })

  it('authenticates and sets token on successful login', async () => {
    const user = userEvent.setup()
    renderLogin()
    await user.type(screen.getByLabelText('Email'), 'test@example.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in/i }))
    await waitFor(() => {
      expect(useAuthStore.getState().isAuthenticated).toBe(true)
    })
  })
})
