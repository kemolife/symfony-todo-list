# ADR-0001: JWT Authentication via lexik/jwt-authentication-bundle

Date: 2026-04-30  
Status: Accepted

## Context

The API needs stateless authentication across backend and React frontend. Session-based auth requires sticky sessions or a shared session store, adding infrastructure complexity.

## Decision

Use `lexik/jwt-authentication-bundle` for JWT authentication. Token payload includes `roles`, `email`, and `twoFactorConfirmed`. Tokens are injected by Axios on every request via `Authorization: Bearer`.

## Consequences

- Stateless — no session storage needed
- Frontend decodes JWT to extract roles and 2FA status without extra requests
- Token expiry must be handled; 401 clears auth state and redirects to `/login`
- Token payload is readable client-side — never include secrets in it
