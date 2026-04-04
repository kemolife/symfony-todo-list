import type { SelectHTMLAttributes } from 'react'
import { forwardRef } from 'react'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  placeholder?: string
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ placeholder, children, className = '', ...props }, ref) => (
    <select
      ref={ref}
      {...props}
      className={`rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${className}`}
    >
      {placeholder && <option value="">{placeholder}</option>}
      {children}
    </select>
  ),
)
Select.displayName = 'Select'
