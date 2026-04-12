import { useDashboard } from '@/api/useDashboard'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

export function DashboardOverview() {
  const { data, isLoading, isError } = useDashboard()

  return (
    <div className="space-y-4">
      <h2 className="text-xl font-semibold">Overview</h2>

      <Card>
        <CardHeader>
          <CardTitle>Welcome</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && <Skeleton className="h-4 w-48" />}
          {isError && <p className="text-sm text-destructive">Failed to load dashboard data.</p>}
          {data && <p className="text-sm text-muted-foreground">{data.message}</p>}
        </CardContent>
      </Card>
    </div>
  )
}
