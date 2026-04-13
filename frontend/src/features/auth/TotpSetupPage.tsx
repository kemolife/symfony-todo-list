import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Copy, CheckCheck, ShieldCheck, Loader2 } from 'lucide-react'
import { useSetup2fa, useConfirm2fa } from '@/api/useAuth'
import { useAuthStore } from '@/store/authStore'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'

const schema = z.object({
  code: z
    .string()
    .length(6, 'Code must be exactly 6 digits')
    .regex(/^\d{6}$/, 'Code must contain only digits'),
})

type FormData = z.infer<typeof schema>

export function TotpSetupPage() {
  const navigate = useNavigate()
  const { data, isLoading, isError } = useSetup2fa()
  const confirm2fa = useConfirm2fa()
  const clearTwoFactorSetupPending = useAuthStore((s) => s.clearTwoFactorSetupPending)
  const [copied, setCopied] = useState(false)

  useEffect(() => {
    if (isError) clearTwoFactorSetupPending()
  }, [isError, clearTwoFactorSetupPending])

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({ resolver: zodResolver(schema) })

  const copySecret = async () => {
    if (!data) return
    await navigator.clipboard.writeText(data.totp_secret)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const onSubmit = async (formData: FormData) => {
    try {
      await confirm2fa.mutateAsync({ code: formData.code })
      toast.success('Two-factor authentication enabled')
      navigate('/dashboard', { replace: true })
    } catch {
      toast.error('Invalid code — try again')
    }
  }

  if (isLoading) {
    return (
      <div className="flex min-h-svh items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    )
  }

  if (isError || !data) {
    return (
      <div className="flex min-h-svh items-center justify-center px-4">
        <Card className="w-full max-w-sm">
          <CardContent className="pt-6 text-center">
            <p className="text-sm text-muted-foreground">
              2FA is already set up or this account does not require it.
            </p>
            <Button className="mt-4 w-full" onClick={() => navigate('/dashboard', { replace: true })}>
              Go to dashboard
            </Button>
          </CardContent>
        </Card>
      </div>
    )
  }

  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.totp_uri)}`

  return (
    <div className="flex min-h-svh items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-6 w-6 text-primary" />
            <CardTitle className="text-2xl">Set up 2FA</CardTitle>
          </div>
          <CardDescription>
            Scan the QR code with your authenticator app, then enter the 6-digit code to confirm.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          <div className="flex justify-center rounded-lg border bg-white p-3">
            <img src={qrUrl} alt="TOTP QR code" width={200} height={200} />
          </div>

          <div className="space-y-1.5">
            <Label>Manual entry key</Label>
            <div className="flex items-center gap-2">
              <Input readOnly value={data.totp_secret} className="font-mono text-xs" />
              <Button variant="outline" size="icon" onClick={copySecret} title="Copy secret">
                {copied ? (
                  <CheckCheck className="h-4 w-4 text-green-500" />
                ) : (
                  <Copy className="h-4 w-4" />
                )}
              </Button>
            </div>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-3">
            <div className="space-y-1.5">
              <Label htmlFor="code">Verification code</Label>
              <Input
                id="code"
                inputMode="numeric"
                maxLength={6}
                placeholder="000000"
                className="font-mono tracking-widest"
                {...register('code')}
              />
              {errors.code && (
                <p className="text-xs text-destructive">{errors.code.message}</p>
              )}
            </div>
            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? 'Confirming…' : 'Confirm & enable 2FA'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
