# Architecture Decision Records

Format: [Michael Nygard](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions)

| ADR | Title | Status |
|-----|-------|--------|
| [0001](0001-jwt-authentication.md) | JWT Authentication via lexik/jwt-authentication-bundle | Accepted |
| [0002](0002-totp-two-factor-auth.md) | TOTP Two-Factor Authentication | Accepted |
| [0003](0003-symfony-messenger-rabbitmq.md) | Symfony Messenger with RabbitMQ | Accepted |
| [0004](0004-request-dtos-map-request-payload.md) | Request DTOs with #[MapRequestPayload] | Accepted |
| [0005](0005-response-dtos-from-entity-factories.md) | Readonly Response DTOs with fromEntity() Factories | Accepted |
| [0006](0006-todo-voter-resource-authorization.md) | TodoVoter for Resource-Level Authorization | Accepted |
| [0007](0007-dama-bundle-test-isolation.md) | DAMA Bundle for Test Database Isolation | Accepted |
| [0008](0008-zustand-frontend-state.md) | Zustand for Frontend State Management | Accepted |
| [0009](0009-msw-frontend-test-mocking.md) | MSW for Frontend API Mocking in Tests | Accepted |
| [0010](0010-repository-query-building-pattern.md) | Shared Query Builder Methods in Repositories | Accepted |
| [0011](0011-list-completion-policy.md) | ListCompletionPolicy as Single Owner of List-Item Completion Invariant | Accepted |

## Adding New ADRs

1. Copy an existing file, increment the number
2. Set status to `Proposed` until agreed
3. Add row to this table
