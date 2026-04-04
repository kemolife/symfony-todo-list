import type { ButtonHTMLAttributes } from 'react'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger'
  size?: 'sm' | 'md'
}

const variants = {
  primary: 'bg-blue-600 text-white hover:bg-blue-700',
  secondary: 'bg-gray-100 text-gray-700 hover:bg-gray-200',
  danger: 'bg-red-600 text-white hover:bg-red-700',
}

const sizes = {
  sm: 'px-2 py-1 text-sm',
  md: 'px-4 py-2 text-sm',
}

export function Button({ variant = 'primary', size = 'md', className = '', ...props }: ButtonProps) {
  return (
    <button
      {...props}
      className={`rounded font-medium transition-colors disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${className}`}
    />
  )
}
