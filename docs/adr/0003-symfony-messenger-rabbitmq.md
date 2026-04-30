# ADR-0003: Symfony Messenger with RabbitMQ

Date: 2026-04-30  
Status: Accepted

## Context

Some operations should be processed asynchronously (e.g., notifications, heavy processing) without blocking HTTP responses. The queue broker must support retry and failure handling.

## Decision

Use Symfony Messenger with RabbitMQ as the `async` transport (configured via `MESSENGER_TRANSPORT_DSN` in `.env.local`). Failed messages land in a Doctrine-backed `failed` transport for inspection and retry. Test env uses `in-memory://` to avoid broker dependency in tests.

## Consequences

- Workers run via `./bin/console messenger:consume async -vv`
- No `Message`/`MessageHandler` classes exist yet — routing section in `messenger.yaml` is intentionally empty pending first use
- `in-memory://` in tests means message handling is synchronous and deterministic without a running broker
- RabbitMQ requires Docker Compose to be running locally
