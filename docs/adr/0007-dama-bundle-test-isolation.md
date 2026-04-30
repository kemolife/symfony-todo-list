# ADR-0007: DAMA Bundle for Test Database Isolation

Date: 2026-04-30  
Status: Accepted

## Context

Integration tests need a clean database state per test without truncating and reseeding, which is slow. Transactions can wrap each test and roll back, but this requires consistent setup.

## Decision

Use `dama/doctrine-test-bundle` to wrap each test in a rolled-back transaction automatically. Tests create fixtures inline via helper methods, not Foundry factories. Test users use a dummy bcrypt hash (`'$2y$04$' . str_repeat('a', 53)`) and `when@test` reduces bcrypt cost.

## Consequences

- Each test starts with a clean slate at near-zero cost
- No need to reset sequences or truncate tables between tests
- `loginUser()` bypasses password verification, keeping tests fast
- Tests are independent and can run in any order
- Foundry is not used — fixture helpers live alongside tests
