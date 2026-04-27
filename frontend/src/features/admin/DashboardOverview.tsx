import { ActivityFeed } from './ActivityFeed'

export function DashboardOverview() {
  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold">Overview</h2>
      <ActivityFeed />
    </div>
  )
}
