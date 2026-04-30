# ADR-0011: ListCompletionPolicy as Single Owner of List-Item Completion Invariant

Date: 2026-04-30
Status: Accepted

## Context

The invariant "a TodoList is Done iff all its TodoItems are complete" was enforced across two uncoordinated modules:

- `TodoCompletionSubscriber` — item→list direction: when all items complete, transitions list to Done; when an item uncompletes, transitions list to InProgress
- `MarkListItemsCompleteHandler` — list→items direction (async): when list is set to Done, marks all items complete

`TodoStatusTransitionService` was a shallow pass-through called by both paths. Understanding the full invariant required reading three files simultaneously.

## Decision

Introduce `ListCompletionPolicy` as the single module owning the bidirectional completion invariant. It absorbs `TodoStatusTransitionService` and the detection logic from `TodoCompletionSubscriber`.

- `TodoItemService` calls `completionPolicy->handleItemCompleted/Uncompleted()` directly before flushing, then dispatches `TodoItemCompleted/UncompletedEvent` for extension consumers
- `TodoService` calls `completionPolicy->setListStatus()` (replaces `transitionService->transition()`)
- `MarkListItemsCompleteHandler` calls `completionPolicy->cascadeItemCompletion()` (replaces inline item loop)
- `TodoCompletionSubscriber` and `TodoStatusTransitionService` are deleted

The list→items cascade remains async (via `MarkListItemsCompleteMessage` / RabbitMQ) — this is intentional; eventual consistency is acceptable for item state.

## Consequences

- Completion invariant has one owner and one place to test
- Adding a third direction (e.g., scheduled completion) extends `ListCompletionPolicy` only
- `TodoItemCompleted/UncompletedEvent` are kept as extension hooks for future consumers (audit, notifications) but no longer drive completion logic
- `em->flush()` remains in callers; `ListCompletionPolicy` is free of persistence concerns
