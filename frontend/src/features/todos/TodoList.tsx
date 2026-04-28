import { useTodos, useImportTodos, type ColumnMap } from '@/api/useTodos'
import { CsvMapperDialog } from './CsvMapperDialog'
import { useModalStore } from '@/store/modalStore'
import { useTodoFilterStore } from '@/store/todoFilterStore'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Plus, ClipboardList, ChevronLeft, ChevronRight, Upload, X } from 'lucide-react'
import { useRef, useState } from 'react'
import { TodoCard } from './TodoCard'
import { TodoFilters } from './TodoFilters'
import { TodoForm } from './TodoForm'

function TodoSkeleton() {
  return (
    <div className="flex items-start gap-4 rounded-lg border bg-card p-4">
      <Skeleton className="mt-0.5 h-4 w-4 rounded" />
      <div className="flex-1 space-y-2">
        <Skeleton className="h-4 w-3/4" />
        <Skeleton className="h-3 w-1/2" />
        <Skeleton className="h-5 w-20 rounded-full" />
      </div>
    </div>
  )
}

function PaginationControls({ page, pages, onPageChange }: { page: number; pages: number; onPageChange: (p: number) => void }) {
  if (pages <= 1) return null

  const getPageNumbers = () => {
    const delta = 2
    const range: number[] = []
    const left = Math.max(2, page - delta)
    const right = Math.min(pages - 1, page + delta)

    range.push(1)
    if (left > 2) range.push(-1)
    for (let i = left; i <= right; i++) range.push(i)
    if (right < pages - 1) range.push(-2)
    if (pages > 1) range.push(pages)

    return range
  }

  return (
    <div className="flex items-center justify-center gap-1 pt-2">
      <Button variant="outline" size="sm" onClick={() => onPageChange(page - 1)} disabled={page <= 1} className="h-8 w-8 p-0">
        <ChevronLeft className="h-4 w-4" />
      </Button>

      {getPageNumbers().map((p, i) =>
        p < 0 ? (
          <span key={p + '_' + i} className="px-1 text-muted-foreground">…</span>
        ) : (
          <Button key={p} variant={p === page ? 'default' : 'outline'} size="sm" onClick={() => onPageChange(p)} className="h-8 w-8 p-0">
            {p}
          </Button>
        ),
      )}

      <Button variant="outline" size="sm" onClick={() => onPageChange(page + 1)} disabled={page >= pages} className="h-8 w-8 p-0">
        <ChevronRight className="h-4 w-4" />
      </Button>
    </div>
  )
}

export function TodoList() {
  const filters = useTodoFilterStore((s) => s.filters)
  const setPage = useTodoFilterStore((s) => s.setPage)
  const { data: paginated, isLoading, error } = useTodos(filters)
  const { isCreateOpen, editingTodoId, openCreate, close } = useModalStore()
  const importTodos = useImportTodos()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [importMessage, setImportMessage] = useState<string | null>(null)
  const [importHadErrors, setImportHadErrors] = useState(false)
  const [isDragging, setIsDragging] = useState(false)
  const [pendingFile, setPendingFile] = useState<File | null>(null)
  const dragCounter = useRef(0)

  const triggerImport = ({ file, columnMap }: { file: File; columnMap: ColumnMap }) => {
    setPendingFile(null)
    importTodos.mutate({ file, columnMap }, {
      onSuccess: (result) => {
        const parts = [`Imported ${result.created} todo(s).`]
        if (result.failed > 0) parts.push(`${result.failed} row(s) failed.`)
        if (result.errors.length > 0) parts.push(result.errors.join(' '))
        setImportHadErrors(result.failed > 0)
        setImportMessage(parts.join(' '))
      },
      onError: () => {
        setImportHadErrors(true)
        setImportMessage('Import failed')
      },
    })
  }

  const handleImportChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return
    e.target.value = ''
    setPendingFile(file)
  }

  const handleDragEnter = (e: React.DragEvent) => {
    if (!e.dataTransfer?.types.includes('Files')) return
    dragCounter.current++
    setIsDragging(true)
  }
  const handleDragLeave = () => {
    dragCounter.current--
    if (dragCounter.current === 0) setIsDragging(false)
  }
  const handleDragOver = (e: React.DragEvent) => e.preventDefault()
  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    dragCounter.current = 0
    setIsDragging(false)
    const file = e.dataTransfer?.files[0]
    if (file) setPendingFile(file)
  }

  const todos = paginated?.items
  const page = paginated?.page ?? 1
  const pages = paginated?.pages ?? 1
  const total = paginated?.total ?? 0

  return (
    <div
      className="space-y-4"
      onDragEnter={handleDragEnter}
      onDragLeave={handleDragLeave}
      onDragOver={handleDragOver}
      onDrop={handleDrop}
    >
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <ClipboardList className="h-6 w-6 text-primary" />
          <h1 className="text-2xl font-semibold tracking-tight">My Todos</h1>
          {paginated && (
            <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
              {total}
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          <input ref={fileInputRef} type="file" accept=".csv" className="hidden" onChange={handleImportChange} />
          <Button variant="outline" className="gap-2" disabled={importTodos.isPending} onClick={() => fileInputRef.current?.click()}>
            <Upload className="h-4 w-4" />
            {importTodos.isPending ? 'Importing…' : 'Import CSV'}
          </Button>
          <Button onClick={openCreate} className="gap-2">
            <Plus className="h-4 w-4" />
            New todo
          </Button>
        </div>
      </div>

      <Separator />

      {importMessage && (
        <div className={`flex items-start justify-between gap-2 rounded-lg border p-3 text-sm ${importHadErrors ? 'border-destructive/30 bg-destructive/10 text-destructive' : 'border-green-200 bg-green-50 text-green-800'}`}>
          <span>{importMessage}</span>
          <button onClick={() => setImportMessage(null)} className="shrink-0 opacity-60 hover:opacity-100"><X className="h-3.5 w-3.5" /></button>
        </div>
      )}

      <TodoFilters />

      {error && (
        <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
          {(error as Error).message}
        </div>
      )}

      {isLoading && (
        <div className="space-y-3">
          {Array.from({ length: 4 }).map((_, i) => <TodoSkeleton key={i} />)}
        </div>
      )}

      {!isLoading && todos?.length === 0 && (
        <div className="flex flex-col items-center gap-3 py-16 text-center">
          <ClipboardList className="h-12 w-12 text-muted-foreground/40" />
          <p className="text-muted-foreground">No todos found.</p>
          <Button variant="outline" onClick={openCreate} className="gap-2">
            <Plus className="h-4 w-4" />
            Create your first todo
          </Button>
        </div>
      )}

      <div className="space-y-2">
        {todos?.map((todo) => <TodoCard key={todo.id} todo={todo} />)}
      </div>

      <PaginationControls page={page} pages={pages} onPageChange={setPage} />

      <Dialog open={isCreateOpen} onOpenChange={(open) => !open && close()}>
        <DialogContent>
          <DialogHeader><DialogTitle>New todo</DialogTitle></DialogHeader>
          <TodoForm onSuccess={close} />
        </DialogContent>
      </Dialog>

      <Dialog open={editingTodoId != null} onOpenChange={(open) => !open && close()}>
        <DialogContent>
          <DialogHeader><DialogTitle>Edit todo</DialogTitle></DialogHeader>
          <TodoForm todoId={editingTodoId} onSuccess={close} />
        </DialogContent>
      </Dialog>

      {pendingFile && (
        <CsvMapperDialog file={pendingFile} onConfirm={triggerImport} onClose={() => setPendingFile(null)} />
      )}

      {isDragging && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
          <div className="flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-primary p-16">
            <Upload className="h-12 w-12 text-primary" />
            <p className="text-lg font-semibold">Drop CSV to import</p>
          </div>
        </div>
      )}
    </div>
  )
}
