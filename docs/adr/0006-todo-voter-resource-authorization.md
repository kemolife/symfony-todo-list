# ADR-0006: TodoVoter for Resource-Level Authorization

Date: 2026-04-30  
Status: Accepted

## Context

Class-level `#[IsGranted]` guards endpoints by role but cannot enforce ownership checks (e.g., user A cannot edit user B's todo). Inline ownership checks in controllers duplicate logic and are easy to forget.

## Decision

Use a Symfony Voter (`TodoVoter`) invoked via `denyAccessUnlessGranted(TodoVoter::EDIT, $todo)`. The voter compares owner IDs when both entities are persisted; falls back to object identity for transient objects.

## Consequences

- Ownership logic is centralized and testable independently of controllers
- New permissions (e.g., `VIEW`, `DELETE`) extend the voter without touching controllers
- Transient object fallback handles in-memory test scenarios cleanly
- Forgetting to call `denyAccessUnlessGranted` before a mutation is still possible — code review must catch this
