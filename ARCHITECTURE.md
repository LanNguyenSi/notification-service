# Architecture

This document describes the internal architecture of the Notification Service.

## Design Principles

- Each component compiles and can be tested independently
- Interfaces are the only coupling mechanism between components
- Mock implementations allow development without external services
- No component knows another component's internals

## Component Dependency Graph

```
┌─────────────────────────────────────────────────────────┐
│  TIER 1: Foundation                                      │
├─────────────────────────────────────────────────────────┤
│  Contract (DTOs, Interfaces, Enums, Exceptions)          │
│  Validator (Input validation)                            │
│  Logger (Logging abstraction)                            │
│  ChannelInterface (Abstract channel contract)            │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  TIER 2: Channel Implementations                         │
│  Dependencies: Tier 1 only                               │
├─────────────────────────────────────────────────────────┤
│  EmailChannel (SmtpClientInterface)                      │
│  SMSChannel (SmsGatewayInterface)                        │
│  PushChannel (PushNotificationInterface)                 │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  TIER 3: Core Logic                                      │
│  Dependencies: Tier 1 + Tier 2                           │
├─────────────────────────────────────────────────────────┤
│  Router (Channel selection and availability filtering)   │
│  Dispatcher (Orchestrates validation, routing, delivery) │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  TIER 4: Integration                                     │
│  Dependencies: All previous tiers                        │
├─────────────────────────────────────────────────────────┤
│  Controller (HTTP endpoint, JSON parsing, error mapping) │
│  Kernel (Symfony bootstrap, DI container)                │
└─────────────────────────────────────────────────────────┘
```

## Component Specifications

### Contract (`src/Contract/`)

Foundation types shared across all components.

- **DTOs**: `NotificationRequestDTO`, `NotificationResultDTO`, `DeliveryResultDTO`, `NotificationPayload`, `ValidatedRequest` -- all immutable (`readonly`)
- **Enums**: `Channel` (email, sms, push), `NotificationStatus` (queued, partial, failed, sent)
- **Exceptions**: `ValidationException`, `UnsupportedChannelException`, `DeliveryException`, `NoAvailableChannelException`
- **Interfaces**: `NotificationServiceInterface`, `NotificationValidatorInterface`, `ChannelRouterInterface`

### Validator (`src/Validator/`)

Validates incoming notification requests:

- Non-empty subject (max 200 chars) and body (max 5000 chars)
- At least one delivery channel
- Channel names must map to known `Channel` enum values
- Email format validation (RFC 5322) when the email channel is requested
- Returns a `ValidatedRequest` with parsed `Channel` enums

### Logger (`src/Infrastructure/Logger/`)

PSR-3 compatible logging abstraction:

- `StdoutLogger` -- JSON lines to stdout (production/development)
- `NullLogger` -- collects log records in memory (testing)
- All log entries automatically include `notification_id` in context

### ChannelInterface (`src/Channel/ChannelInterface.php`)

Contract that all delivery channels implement:

```php
interface ChannelInterface {
    public function getName(): Channel;
    public function send(NotificationPayload $payload): DeliveryResultDTO;
    public function isAvailable(): bool;
}
```

### Channel Implementations (`src/Channel/`)

Each channel follows the same pattern: validate recipient format, call the provider interface, return a `DeliveryResultDTO`. All channels are idempotent per notification ID.

| Channel      | Provider Interface           | Recipient Validation        | Limits               |
|-------------|------------------------------|-----------------------------|-----------------------|
| EmailChannel | `SmtpClientInterface`        | RFC 5322 email              | HTML body sanitized   |
| SMSChannel   | `SmsGatewayInterface`        | E.164 phone number          | Body truncated at 160 |
| PushChannel  | `PushNotificationInterface`  | Token `[A-Za-z0-9:_-]{16,255}` | Payload max 4 KB  |

### Router (`src/Router/`)

`ChannelRouter` maps channel name strings to `ChannelInterface` implementations. Filters out unavailable channels (with a warning log). Throws `UnsupportedChannelException` for unknown channel names.

### Dispatcher (`src/Service/`)

`NotificationDispatcher` orchestrates the full delivery flow:

1. Validate the request
2. Generate a UUID v4 notification ID
3. Resolve channels via the router
4. Send to each channel, catching per-channel failures
5. Aggregate results: `sent` (all OK), `partial` (some failed), `failed` (all failed)

### Controller (`src/Controller/`)

- `NotificationController` -- `POST /api/v1/notifications`, returns JSON result or RFC 7807 problem details
- `HealthController` -- `GET /health`, returns `{"status": "ok"}`

Both controllers attach an `X-Request-ID` header to every response.

## Service Wiring

All services are wired via Symfony's DI container in `config/services.yaml`. Channels are tagged with `app.notification_channel` and injected into the router as a tagged iterator.

Provider interfaces are bound to mock implementations by default. To use real providers, override the interface binding in `services.yaml` or `services_local.yaml`.

## Testing Strategy

- **Unit tests** (`tests/Unit/`): test validator, router, and dispatcher in isolation using mock channels and the `NullLogger`
- **Integration tests** (`tests/Integration/`): boot the full Symfony kernel and test HTTP request/response cycles
- Static analysis via PHPStan at level 8
