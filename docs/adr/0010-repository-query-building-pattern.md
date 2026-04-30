# ADR-0010: Shared Query Builder Methods in Repositories

Date: 2026-04-30  
Status: Accepted

## Context

Paginated list queries need both a count query and a result query with the same filters applied. Duplicating filter logic between count and find methods causes drift when filters are added.

## Decision

Repositories expose `buildFilteredQuery()` and `buildAdminQuery()` as shared internal methods used by both count and find operations. `leftJoin` with `addSelect` is used for eager loading of `TodoItems` to prevent N+1 queries. `TodoStatus::tryFrom($status)` guards enum values before they reach the query builder.

## Consequences

- Adding a filter requires one change, not two
- N+1 on `TodoItems` is structurally prevented at the query layer
- Invalid status values are rejected before hitting the ORM, not after
- Private builder methods are not easily unit-testable in isolation — integration tests cover them via the public API
