import { Routes, Route } from 'react-router-dom'
import { ProtectedRoute } from './components/ProtectedRoute'
import { AdminRoute } from './components/AdminRoute'
import { AppLayout } from './features/layout/AppLayout'
import { TodoList } from './features/todos/TodoList'
import { SettingsPage } from './features/settings/SettingsPage'
import { LoginPage } from './features/auth/LoginPage'
import { RegisterPage } from './features/auth/RegisterPage'
import { AdminRegisterPage } from './features/auth/AdminRegisterPage'
import { TwoFactorPage } from './features/auth/TwoFactorPage'
import { AdminLayout } from './features/admin/AdminLayout'
import { DashboardOverview } from './features/admin/DashboardOverview'
import { UsersPage } from './features/admin/UsersPage'
import { TodosPage } from './features/admin/TodosPage'
import { ApiKeysPage } from './features/admin/ApiKeysPage'
import { TotpSetupPage } from './features/auth/TotpSetupPage'
import { EnrollPage } from './features/auth/EnrollPage'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/admin/register" element={<AdminRegisterPage />} />
      <Route path="/auth/2fa" element={<TwoFactorPage />} />
      <Route path="/2fa/enroll" element={<EnrollPage />} />

      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route path="/" element={<TodoList />} />
          <Route path="/settings" element={<SettingsPage />} />
        </Route>
      </Route>

      <Route element={<AdminRoute />}>
        <Route path="/dashboard/2fa/setup" element={<TotpSetupPage />} />
        <Route element={<AdminLayout />}>
          <Route path="/dashboard" element={<DashboardOverview />} />
          <Route path="/dashboard/users" element={<UsersPage />} />
          <Route path="/dashboard/todos" element={<TodosPage />} />
          <Route path="/dashboard/api-keys" element={<ApiKeysPage />} />
        </Route>
      </Route>
    </Routes>
  )
}
