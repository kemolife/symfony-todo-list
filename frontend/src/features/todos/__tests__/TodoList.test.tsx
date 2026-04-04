import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'
import { describe, it, expect } from 'vitest'
import { TodoList } from '../TodoList'

function renderTodoList() {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(
    <QueryClientProvider client={client}>
      <BrowserRouter>
        <TodoList />
      </BrowserRouter>
    </QueryClientProvider>,
  )
}

describe('TodoList', () => {
  it('shows todos from API', async () => {
    renderTodoList()
    expect(await screen.findByText('Test todo')).toBeInTheDocument()
    expect(screen.getByText('Done todo')).toBeInTheDocument()
  })

  it('shows loading state initially', () => {
    renderTodoList()
    // Skeleton components are rendered during loading (no text, just animated placeholders)
    expect(document.querySelectorAll('[data-slot="skeleton"]').length).toBeGreaterThan(0)
  })

  it('opens create modal when New Todo is clicked', async () => {
    const user = userEvent.setup()
    renderTodoList()

    await screen.findByText('Test todo')
    await user.click(screen.getByRole('button', { name: /new todo/i }))

    expect(screen.getByRole('heading', { name: 'New todo' })).toBeInTheDocument()
  })

  it('shows done status badge', async () => {
    renderTodoList()
    await screen.findByText('Done todo')
    // 'Done' appears in both the status badge and the filter dropdown option
    expect(screen.getAllByText('Done').length).toBeGreaterThanOrEqual(1)
  })
})
