import { Routes, Route } from 'react-router-dom'
import { TodoList } from './features/todos/TodoList'

export default function App() {
  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      <Routes>
        <Route path="/" element={<TodoList />} />
      </Routes>
    </div>
  )
}
