interface BadgeProps {
  label: string
  variant?: 'default' | 'success' | 'warning' | 'info'
}

const variants = {
  default: 'bg-gray-100 text-gray-700',
  success: 'bg-green-100 text-green-800',
  warning: 'bg-yellow-100 text-yellow-800',
  info: 'bg-blue-100 text-blue-800',
}

export function Badge({ label, variant = 'default' }: BadgeProps) {
  return (
    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${variants[variant]}`}>
      {label}
    </span>
  )
}
