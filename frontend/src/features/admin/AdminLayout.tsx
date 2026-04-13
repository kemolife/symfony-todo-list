import { NavLink, Navigate, Outlet, useNavigate } from 'react-router-dom'
import { LayoutDashboard, Users, LogOut, ListTodo } from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const navItems = [
  { to: '/dashboard', label: 'Overview', icon: LayoutDashboard, end: true },
  { to: '/dashboard/users', label: 'Users', icon: Users, end: false },
  { to: '/dashboard/todos', label: 'Todos', icon: ListTodo, end: false },
]

export function AdminLayout() {
  const navigate = useNavigate()
  const clearToken = useAuthStore((s) => s.clearToken)
  const needsTwoFactorSetup = useAuthStore((s) => s.needsTwoFactorSetup)

  if (needsTwoFactorSetup) {
    return <Navigate to="/dashboard/2fa/setup" replace />
  }

  const handleLogout = () => {
    clearToken()
    navigate('/login', { replace: true })
  }

  return (
    <div className="flex min-h-svh">
      <aside className="flex w-56 flex-col border-r bg-background">
        <div className="border-b px-5 py-4">
          <span className="text-sm font-semibold tracking-tight">Admin Panel</span>
        </div>

        <nav className="flex-1 space-y-1 p-3">
          {navItems.map(({ to, label, icon: Icon, end }) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors',
                  isActive
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                )
              }
            >
              <Icon className="h-4 w-4" />
              {label}
            </NavLink>
          ))}
        </nav>

        <div className="border-t p-3">
          <Button
            variant="ghost"
            size="sm"
            className="w-full justify-start text-muted-foreground"
            onClick={handleLogout}
          >
            <LogOut className="mr-2 h-4 w-4" />
            Sign out
          </Button>
        </div>
      </aside>

      <div className="flex flex-1 flex-col">
        <header className="border-b bg-background px-6 py-4">
          <span className="text-lg font-semibold">Admin Dashboard</span>
        </header>

        <main className="flex-1 overflow-auto bg-muted/40 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
