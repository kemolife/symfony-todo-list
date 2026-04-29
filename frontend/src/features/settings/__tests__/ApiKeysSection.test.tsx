import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'
import { describe, it, expect } from 'vitest'
import { ApiKeysSection } from '../ApiKeysSection'

function renderSection() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={client}>
      <BrowserRouter>
        <ApiKeysSection />
      </BrowserRouter>
    </QueryClientProvider>,
  )
}

describe('ApiKeysSection', () => {
  it('shows existing key name', async () => {
    renderSection()
    expect(await screen.findByText('Test key')).toBeInTheDocument()
  })

  it('shows key prefix', async () => {
    renderSection()
    expect(await screen.findByText('a1b2c3d4…')).toBeInTheDocument()
  })

  it('shows permission badge for existing key', async () => {
    renderSection()
    expect(await screen.findByText('read')).toBeInTheDocument()
  })

  it('create button disabled when name is empty', async () => {
    renderSection()
    await screen.findByText('Test key')
    expect(screen.getByRole('button', { name: /create key/i })).toBeDisabled()
  })

  it('create button enabled after entering name', async () => {
    const user = userEvent.setup()
    renderSection()
    await screen.findByText('Test key')
    await user.type(screen.getByPlaceholderText('Key name (e.g. CI pipeline)'), 'My CI key')
    expect(screen.getByRole('button', { name: /create key/i })).toBeEnabled()
  })

  it('creates key and shows one-time key value banner', async () => {
    const user = userEvent.setup()
    renderSection()
    await screen.findByText('Test key')
    await user.type(screen.getByPlaceholderText('Key name (e.g. CI pipeline)'), 'My CI key')
    await user.click(screen.getByRole('button', { name: /create key/i }))
    expect(await screen.findByText(/copy this key now/i)).toBeInTheDocument()
  })

  it('shows confirm and cancel after clicking revoke icon', async () => {
    const user = userEvent.setup()
    renderSection()
    await screen.findByText('Test key')
    // The icon-only revoke button is the first button in the list (before "Create key")
    const buttons = screen.getAllByRole('button')
    const revokeBtn = buttons.find((b) => !b.textContent?.trim())
    expect(revokeBtn).toBeDefined()
    await user.click(revokeBtn!)
    expect(await screen.findByRole('button', { name: /confirm/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument()
  })

  it('revokes key on confirm click', async () => {
    const user = userEvent.setup()
    renderSection()
    await screen.findByText('Test key')
    const buttons = screen.getAllByRole('button')
    const revokeBtn = buttons.find((b) => !b.textContent?.trim())!
    await user.click(revokeBtn)
    await user.click(await screen.findByRole('button', { name: /confirm/i }))
    await waitFor(() => {
      expect(screen.queryByRole('button', { name: /confirm/i })).not.toBeInTheDocument()
    })
  })
})
