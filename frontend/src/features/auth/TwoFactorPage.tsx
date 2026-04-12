import { useEffect, useRef } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { ShieldCheck } from 'lucide-react'
import { useVerify2fa } from '@/api/useAuth'
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

export function TwoFactorPage() {
  const navigate = useNavigate()
  const preAuthToken = useAuthStore((s) => s.preAuthToken)
  const clearPreAuthToken = useAuthStore((s) => s.clearPreAuthToken)
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
  const verify = useVerify2fa()
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (!preAuthToken) navigate('/login', { replace: true })
  }, [preAuthToken, navigate])

  useEffect(() => {
    if (isAuthenticated) navigate('/dashboard', { replace: true })
  }, [isAuthenticated, navigate])

  useEffect(() => {
    inputRef.current?.focus()
  }, [])

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({ resolver: zodResolver(schema) })

  const { ref: formRef, ...codeRest } = register('code')

  const onSubmit = async (data: FormData) => {
    try {
      await verify.mutateAsync({ pre_auth_token: preAuthToken!, code: data.code })
    } catch (e) {
      toast.error((e as Error).message)
    }
  }

  const handleCancel = () => {
    clearPreAuthToken()
    navigate('/login')
  }

  return (
    <div className="flex min-h-svh items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-6 w-6 text-primary" />
            <CardTitle className="text-2xl">Two-factor authentication</CardTitle>
          </div>
          <CardDescription>
            Enter the 6-digit code from your authenticator app.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="code">Authentication code</Label>
              <Input
                id="code"
                inputMode="numeric"
                maxLength={6}
                placeholder="000000"
                className="text-center font-mono text-2xl tracking-widest"
                ref={(el) => {
                  formRef(el)
                  ;(inputRef as React.MutableRefObject<HTMLInputElement | null>).current = el
                }}
                {...codeRest}
              />
              {errors.code && <p className="text-sm text-destructive">{errors.code.message}</p>}
            </div>

            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? 'Verifying…' : 'Verify'}
            </Button>

            <Button type="button" variant="ghost" className="w-full" onClick={handleCancel}>
              Back to sign in
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
