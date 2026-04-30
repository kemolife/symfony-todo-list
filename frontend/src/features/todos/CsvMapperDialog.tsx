import { useEffect, useState } from 'react'
import Papa from 'papaparse'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import type { ColumnMap } from '@/api/useTodos'

interface CsvPreview {
  headers: string[]
  samples: string[][]
}

const ENTITY_FIELDS: { key: keyof ColumnMap; label: string; required: boolean }[] = [
  { key: 'title', label: 'Title', required: true },
  { key: 'description', label: 'Description', required: false },
  { key: 'tag', label: 'Tag', required: false },
  { key: 'status', label: 'Status', required: false },
  { key: 'items', label: 'Items (pipe-separated)', required: false },
]

interface Props {
  file: File
  onConfirm: (payload: { file: File; columnMap: ColumnMap }) => void
  onClose: () => void
}

function parseCsvPreview(file: File): Promise<CsvPreview> {
  return new Promise((resolve, reject) => {
    Papa.parse<string[]>(file, {
      preview: 4,
      complete: ({ data }) => {
        const [headers = [], ...sampleRows] = data
        resolve({ headers, samples: sampleRows })
      },
      error: reject,
    })
  })
}

function buildDefaultMap(headers: string[]): ColumnMap {
  const lower = headers.map((h) => h.toLowerCase())
  const find = (...candidates: string[]) => {
    for (const c of candidates) {
      const idx = lower.indexOf(c)
      if (idx !== -1) return headers[idx] ?? ''
    }
    return ''
  }
  return {
    title: find('title', 'name'),
    description: find('description', 'desc'),
    tag: find('tag', 'tags', 'label'),
    status: find('status', 'state'),
    items: find('items', 'tasks', 'subtasks'),
  }
}

export function CsvMapperDialog({ file, onConfirm, onClose }: Props) {
  const [preview, setPreview] = useState<CsvPreview | null>(null)
  const [columnMap, setColumnMap] = useState<ColumnMap>({
    title: '', description: '', tag: '', status: '', items: '',
  })

  useEffect(() => {
    parseCsvPreview(file).then((p) => {
      setPreview(p)
      setColumnMap(buildDefaultMap(p.headers))
    })
  }, [file])

  const getSample = (csvColumn: string): string => {
    if (!preview || !csvColumn) return ''
    const idx = preview.headers.indexOf(csvColumn)
    if (idx === -1) return ''
    return preview.samples
      .map((row) => row[idx] ?? '')
      .filter(Boolean)
      .slice(0, 2)
      .join(', ')
  }

  const setField = (key: keyof ColumnMap, value: string) =>
    setColumnMap((prev) => ({ ...prev, [key]: value }))

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-4xl sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle>Map CSV columns</DialogTitle>
        </DialogHeader>

        {!preview ? (
          <p className="text-sm text-muted-foreground">Reading file…</p>
        ) : (
          <div className="space-y-4">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="pb-2 pr-4 font-medium text-muted-foreground">Field</th>
                  <th className="pb-2 pr-4 font-medium text-muted-foreground">CSV column</th>
                  <th className="pb-2 font-medium text-muted-foreground">Sample values</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {ENTITY_FIELDS.map(({ key, label, required }) => (
                  <tr key={key}>
                    <td className="py-2.5 pr-4 font-medium">
                      {label}
                      {required && <span className="ml-0.5 text-destructive">*</span>}
                    </td>
                    <td className="py-2.5 pr-4">
                      <select
                        value={columnMap[key]}
                        onChange={(e) => setField(key, e.target.value)}
                        className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                      >
                        {!required && <option value="">— skip —</option>}
                        {preview.headers.map((h) => (
                          <option key={h} value={h}>{h}</option>
                        ))}
                      </select>
                    </td>
                    <td className="max-w-[200px] truncate py-2.5 text-muted-foreground">
                      {getSample(columnMap[key]) || <span className="italic opacity-40">—</span>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div className="flex justify-end gap-2 border-t pt-4">
              <Button variant="outline" onClick={onClose}>Cancel</Button>
              <Button
                onClick={() => onConfirm({ file, columnMap })}
                disabled={!columnMap.title}
              >
                Import
              </Button>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  )
}
