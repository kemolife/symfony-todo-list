# ADR-0002: TOTP Two-Factor Authentication

Date: 2026-04-30  
Status: Accepted

## Context

Admin accounts need stronger authentication. SMS-based 2FA requires a third-party service; TOTP is standard, offline-capable, and requires no external dependency beyond a shared secret.

## Decision

Implement TOTP 2FA using `scheb/2fa-bundle`. `User` implements `TwoFactorInterface`. Login flow for admins with pending 2FA returns `{ two_factor_required: true, pre_auth_token }` instead of a full JWT. The pre-auth token is cached with 300 s TTL. `__serialize()` uses CRC32C hash for session safety.

## Consequences

- Admin accounts protected by second factor
- 2FA enrollment page at `/2fa/enroll`; verification at `/auth/2fa`
- Pre-auth token pattern means the normal JWT path is not reachable until TOTP confirmed
- `twoFactorConfirmed` in JWT payload lets the frontend gate 2FA-required UI without extra round-trips
