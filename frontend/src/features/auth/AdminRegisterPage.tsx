import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link } from 'react-router-dom'
import { toast } from 'sonner'
import { Copy, CheckCheck, ShieldCheck } from 'lucide-react'
import { useAdminRegister, type AdminRegisterResponse } from '@/api/useAuth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'

const schema = z
  .object({
    email: z.email('Enter a valid email'),
    password: z
      .string()
      .min(8, 'Password must be at least 8 characters')
      .regex(
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/,
        'Must contain uppercase, lowercase, number and special character',
      ),
    password_confirmation: z.string(),
    admin_secret: z.string().min(1, 'Admin secret is required'),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

type FormData = z.infer<typeof schema>

function TotpSetup({ result }: { result: AdminRegisterResponse }) {
  const [copied, setCopied] = useState(false)
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(result.totp_uri)}`

  const copySecret = async () => {
    await navigator.clipboard.writeText(result.totp_secret)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="flex min-h-svh items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-6 w-6 text-primary" />
            <CardTitle className="text-2xl">Set up 2FA</CardTitle>
          </div>
          <CardDescription>
            Scan the QR code with your authenticator app (e.g. Google Authenticator).
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          <div className="flex justify-center rounded-lg border bg-white p-3">
            <img src={qrUrl} alt="TOTP QR code" width={200} height={200} />
          </div>

          <div className="space-y-1.5">
            <Label>Manual entry key</Label>
            <div className="flex items-center gap-2">
              <Input readOnly value={result.totp_secret} className="font-mono text-xs" />
              <Button variant="outline" size="icon" onClick={copySecret} title="Copy secret">
                {copied ? <CheckCheck className="h-4 w-4 text-green-500" /> : <Copy className="h-4 w-4" />}
              </Button>
            </div>
          </div>

          <Button asChild className="w-full">
            <Link to="/login">Continue to sign in</Link>
          </Button>
        </CardContent>
      </Card>
    </div>
  )
}

export function AdminRegisterPage() {
  const adminRegister = useAdminRegister()
  const [totpResult, setTotpResult] = useState<AdminRegisterResponse | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({ resolver: zodResolver(schema) })

  const onSubmit = async (data: FormData) => {
    try {
      const result = await adminRegister.mutateAsync(data)
      setTotpResult(result)
    } catch (e) {
      toast.error((e as Error).message)
    }
  }

  if (totpResult) {
    return <TotpSetup result={totpResult} />
  }

  return (
    <div className="flex min-h-svh items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle className="text-2xl">Create admin account</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" placeholder="admin@example.com" {...register('email')} />
              {errors.email && <p className="text-sm text-destructive">{errors.email.message}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="password">Password</Label>
              <Input id="password" type="password" {...register('password')} />
              {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="password_confirmation">Confirm password</Label>
              <Input id="password_confirmation" type="password" {...register('password_confirmation')} />
              {errors.password_confirmation && (
                <p className="text-sm text-destructive">{errors.password_confirmation.message}</p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="admin_secret">Admin secret</Label>
              <Input id="admin_secret" type="password" {...register('admin_secret')} />
              {errors.admin_secret && <p className="text-sm text-destructive">{errors.admin_secret.message}</p>}
            </div>

            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? 'Creating…' : 'Create admin account'}
            </Button>

            <p className="text-center text-sm text-muted-foreground">
              Already have an account?{' '}
              <Link to="/login" className="underline underline-offset-4 hover:text-primary">
                Sign in
              </Link>
            </p>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
