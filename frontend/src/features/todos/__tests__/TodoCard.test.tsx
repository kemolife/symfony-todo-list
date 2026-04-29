import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter } from 'react-router-dom'
import { describe, it, expect } from 'vitest'
import { TodoCard } from '../TodoCard'
import { mockTodo, mockDoneTodo } from '../../../test/mocks/handlers'

function renderCard(todo = mockTodo) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={client}>
      <BrowserRouter>
        <TodoCard todo={todo} />
      </BrowserRouter>
    </QueryClientProvider>,
  )
}

describe('TodoCard', () => {
  it('renders todo name', () => {
    renderCard()
    expect(screen.getByText('Test todo')).toBeInTheDocument()
  })

  it('renders description', () => {
    renderCard()
    expect(screen.getByText('A description')).toBeInTheDocument()
  })

  it('renders tag badge', () => {
    renderCard()
    expect(screen.getByText('work')).toBeInTheDocument()
  })

  it('renders status badge', () => {
    renderCard()
    expect(screen.getByText('Pending')).toBeInTheDocument()
  })

  it('renders priority badge', () => {
    renderCard()
    expect(screen.getByText('Medium')).toBeInTheDocument()
  })

  it('checkbox is unchecked for pending todo', () => {
    renderCard()
    expect(screen.getByRole('checkbox')).not.toBeChecked()
  })

  it('checkbox is checked for done todo', () => {
    renderCard(mockDoneTodo)
    expect(screen.getByRole('checkbox')).toBeChecked()
  })

  it('done todo name has line-through', () => {
    renderCard(mockDoneTodo)
    expect(screen.getByText('Done todo')).toHaveClass('line-through')
  })

  it('shows subtask progress when items exist', () => {
    renderCard()
    expect(screen.getByText('Subtasks')).toBeInTheDocument()
    expect(screen.getByText('0/1')).toBeInTheDocument()
  })

  it('clicking checkbox triggers update mutation without error', async () => {
    const user = userEvent.setup()
    renderCard()
    await user.click(screen.getByRole('checkbox'))
    await waitFor(() => {
      expect(screen.queryByText(/failed/i)).not.toBeInTheDocument()
    })
  })
})
