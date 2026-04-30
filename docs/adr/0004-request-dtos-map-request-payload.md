# ADR-0004: Request DTOs with #[MapRequestPayload]

Date: 2026-04-30  
Status: Accepted

## Context

Controllers need to validate incoming JSON bodies. Doing validation manually in each controller method is repetitive and error-prone. Framework-level deserialization and validation gives a consistent 422 response before the controller method runs.

## Decision

Use Symfony's `#[MapRequestPayload]` attribute on controller method parameters. Request DTOs have public properties with Symfony Validator constraints. Context-specific rules use `validationGroups: ['create']`.

## Consequences

- Invalid bodies return 422 before any controller logic executes
- Controllers stay thin — no manual `$request->getContent()` / `json_decode` boilerplate
- DTOs are self-documenting via constraint attributes
- Validation groups allow reusing the same DTO for create vs. update with different required fields
