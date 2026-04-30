# ADR-0005: Readonly Response DTOs with fromEntity() Factories

Date: 2026-04-30  
Status: Accepted

## Context

Returning Doctrine entities directly from controllers leaks internal structure, triggers lazy-loading issues, and couples the API contract to the ORM model.

## Decision

All controller responses use `readonly` response DTOs constructed via static `fromEntity()` factories. Two shapes exist: `TodoResponse` (user view with nested `TodoItemResponse`) and `AdminTodoResponse` (adds owner email and ID).

## Consequences

- API contract is decoupled from entity internals
- `readonly` prevents accidental mutation after construction
- `fromEntity()` factories keep construction logic colocated with the DTO definition
- Adding admin-only fields requires a separate DTO, not conditional logic in a single class
