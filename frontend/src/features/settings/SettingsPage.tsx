import { Separator } from '@/components/ui/separator'
import { ProfileSection } from './ProfileSection'
import { SecuritySection } from './SecuritySection'
import { ApiKeysSection } from './ApiKeysSection'

function Section({ title, description, children }: { title: string; description?: string; children: React.ReactNode }) {
  return (
    <section className="space-y-4">
      <div>
        <h2 className="text-base font-semibold">{title}</h2>
        {description && <p className="text-sm text-muted-foreground">{description}</p>}
      </div>
      {children}
    </section>
  )
}

export function SettingsPage() {
  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-xl font-semibold">Settings</h1>
        <p className="text-sm text-muted-foreground">Manage your account and preferences.</p>
      </div>

      <Section title="Profile" description="Update your display name and view account details.">
        <ProfileSection />
      </Section>

      <Separator />

      <Section title="Security" description="Change your password.">
        <SecuritySection />
      </Section>

      <Separator />

      <Section title="API Keys" description="Create and manage API keys for programmatic access.">
        <ApiKeysSection />
      </Section>
    </div>
  )
}
