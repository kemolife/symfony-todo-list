import { useRef, useState } from 'react'
import { useImportTodos, type ColumnMap } from '@/api/useTodos'

export interface CsvImportState {
  isPending: boolean
  message: string | null
  hadErrors: boolean
  isDragging: boolean
  pendingFile: File | null
  fileInputProps: {
    ref: React.RefObject<HTMLInputElement | null>
    onChange: (e: React.ChangeEvent<HTMLInputElement>) => void
  }
  dragHandlers: {
    onDragEnter: (e: React.DragEvent) => void
    onDragLeave: () => void
    onDragOver: (e: React.DragEvent) => void
    onDrop: (e: React.DragEvent) => void
  }
  triggerFileInput: () => void
  confirm: (payload: { file: File; columnMap: ColumnMap }) => void
  dismissPending: () => void
  clearMessage: () => void
}

export function useCsvImport(): CsvImportState {
  const importTodos = useImportTodos()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const dragCounter = useRef(0)
  const [isDragging, setIsDragging] = useState(false)
  const [pendingFile, setPendingFile] = useState<File | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [hadErrors, setHadErrors] = useState(false)

  const confirm = ({ file, columnMap }: { file: File; columnMap: ColumnMap }) => {
    setPendingFile(null)
    importTodos.mutate({ file, columnMap }, {
      onSuccess: (result) => {
        const parts = [`Imported ${result.created} todo(s).`]
        if (result.failed > 0) parts.push(`${result.failed} row(s) failed.`)
        if (result.errors.length > 0) parts.push(result.errors.join(' '))
        setHadErrors(result.failed > 0)
        setMessage(parts.join(' '))
      },
      onError: () => {
        setHadErrors(true)
        setMessage('Import failed')
      },
    })
  }

  return {
    isPending: importTodos.isPending,
    message,
    hadErrors,
    isDragging,
    pendingFile,
    fileInputProps: {
      ref: fileInputRef,
      onChange: (e) => {
        const file = e.target.files?.[0]
        if (!file) return
        e.target.value = ''
        setPendingFile(file)
      },
    },
    dragHandlers: {
      onDragEnter: (e) => {
        if (!e.dataTransfer?.types.includes('Files')) return
        dragCounter.current++
        setIsDragging(true)
      },
      onDragLeave: () => {
        dragCounter.current--
        if (dragCounter.current === 0) setIsDragging(false)
      },
      onDragOver: (e) => e.preventDefault(),
      onDrop: (e) => {
        e.preventDefault()
        dragCounter.current = 0
        setIsDragging(false)
        const file = e.dataTransfer?.files[0]
        if (file) setPendingFile(file)
      },
    },
    triggerFileInput: () => fileInputRef.current?.click(),
    confirm,
    dismissPending: () => setPendingFile(null),
    clearMessage: () => setMessage(null),
  }
}
