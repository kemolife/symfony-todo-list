import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { describe, it, expect, vi } from 'vitest'
import { TodoForm } from '../TodoForm'

function renderForm(props: { todoId?: number | null; onSuccess?: () => void }) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(
    <QueryClientProvider client={client}>
      <TodoForm todoId={props.todoId ?? null} onSuccess={props.onSuccess ?? vi.fn()} />
    </QueryClientProvider>,
  )
}

describe('TodoForm', () => {
  it('shows validation error when name is empty', async () => {
    const user = userEvent.setup()
    renderForm({})

    await user.click(screen.getByRole('button', { name: /create/i }))

    expect(await screen.findByText('Name is required')).toBeInTheDocument()
  })

  it('calls onSuccess after successful create', async () => {
    const user = userEvent.setup()
    const onSuccess = vi.fn()
    renderForm({ onSuccess })

    await user.type(screen.getByPlaceholderText('What needs to be done?'), 'New todo')
    await user.click(screen.getByRole('button', { name: /create/i }))

    await waitFor(() => expect(onSuccess).toHaveBeenCalledOnce())
  })
})
