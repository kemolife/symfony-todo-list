# ADR-0008: Zustand for Frontend State Management

Date: 2026-04-30  
Status: Accepted

## Context

The React frontend needs to manage auth state (with localStorage persistence), filter/pagination state, and modal state. Redux adds significant boilerplate; React Context re-renders on every update.

## Decision

Use Zustand with three stores:
- `useAuthStore` — persisted to `localStorage`; decodes JWT for roles, email, `twoFactorConfirmed`; `isAdmin()` helper
- `useTodoFilterStore` — pagination + filters; resets page to 1 on non-page filter changes
- `useModalStore` — create/edit modals are mutually exclusive

## Consequences

- Minimal boilerplate; stores are plain functions
- `localStorage` persistence survives page refresh for auth state
- Stores are independently subscribable — components only re-render when their slice changes
- JWT decoding client-side avoids extra `/me` round-trips on load
- Mutually exclusive modal invariant is enforced in the store, not scattered across components
