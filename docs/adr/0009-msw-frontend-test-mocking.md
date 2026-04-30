# ADR-0009: MSW for Frontend API Mocking in Tests

Date: 2026-04-30  
Status: Accepted

## Context

Frontend tests need to simulate API responses without hitting a real server. Mocking Axios directly is brittle and couples tests to implementation details. A network-level interceptor tests the full request/response cycle.

## Decision

Use Mock Service Worker (MSW) to intercept all API calls at the network level. Handlers live in `src/test/mocks/handlers.ts`. No real network requests occur in tests.

## Consequences

- Tests exercise the actual Axios instance including interceptors (auth header injection, 401 redirect)
- Handlers are reusable across multiple test files
- Switching the HTTP client in the future does not require rewriting mock setup
- MSW handlers must be kept in sync with real API shape — drift is a risk
