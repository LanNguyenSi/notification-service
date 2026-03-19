# Notification Service

A lightweight, channel-agnostic notification service built with PHP and Symfony. Supports **Email**, **SMS**, and **Push** delivery through a single HTTP API.

## Features

- **Multi-channel delivery** -- send notifications via email, SMS, and push in a single request
- **Partial failure handling** -- individual channel failures don't block other channels
- **Input validation** -- email format (RFC 5322), E.164 phone numbers, push token format, length limits
- **RFC 7807 error responses** -- standardized problem details for all error cases
- **Request tracing** -- every response includes an `X-Request-ID` header
- **Idempotent channels** -- duplicate sends for the same notification are deduplicated
- **Clean architecture** -- contracts, channels, router, dispatcher are fully decoupled via interfaces
- **Pluggable providers** -- swap mock providers for real ones (SMTP, Twilio, FCM) by implementing the interface

## Requirements

- PHP 8.1+
- Composer 2+

## Quick Start

```bash
git clone https://github.com/pandora/notification-service.git
cd notification-service
composer install
cp .env .env.local   # adjust settings if needed
php -S 127.0.0.1:8000 -t public
```

### Send a notification

```bash
curl -X POST http://127.0.0.1:8000/api/v1/notifications \
  -H "Content-Type: application/json" \
  -d '{
    "recipient": "user@example.com",
    "channels": ["email"],
    "subject": "Welcome",
    "body": "Welcome to our service!"
  }'
```

Response:

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "sent",
  "deliveries": [
    {
      "channel": "email",
      "status": "sent",
      "errorMessage": null,
      "timestamp": "2026-03-19T12:00:00+00:00"
    }
  ]
}
```

### Multi-channel delivery

```bash
curl -X POST http://127.0.0.1:8000/api/v1/notifications \
  -H "Content-Type: application/json" \
  -d '{
    "recipient": "+4915112345678",
    "channels": ["sms", "push"],
    "subject": "Alert",
    "body": "System update available."
  }'
```

### Health check

```bash
curl http://127.0.0.1:8000/health
# {"status":"ok"}
```

## API Reference

### `POST /api/v1/notifications`

| Field       | Type     | Required | Description                              |
|-------------|----------|----------|------------------------------------------|
| `recipient` | string   | yes      | Email address, phone number, or token    |
| `channels`  | string[] | yes      | One or more of: `email`, `sms`, `push`   |
| `subject`   | string   | yes      | Notification subject (max 200 chars)     |
| `body`      | string   | yes      | Notification body (max 5000 chars)       |
| `metadata`  | object   | no       | Extra data (e.g. `{"format": "html"}`)   |

**Responses:**

| Status | Description                                          |
|--------|------------------------------------------------------|
| 200    | Notification processed (check `status` field)        |
| 400    | Validation error (RFC 7807 problem details)          |
| 503    | No channels available (RFC 7807 problem details)     |
| 500    | Internal error (RFC 7807 problem details)            |

**Status values:** `sent` (all channels succeeded), `partial` (some failed), `failed` (all failed)

### `GET /health`

Returns `{"status": "ok"}` when the service is running.

## Architecture

```
Request -> Controller -> Dispatcher -> Validator
                                    -> Router -> Channel[]
                                                   ├── EmailChannel  (SmtpClientInterface)
                                                   ├── SMSChannel    (SmsGatewayInterface)
                                                   └── PushChannel   (PushNotificationInterface)
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed component specifications.

### Project structure

```
src/
├── Contract/              # DTOs, Enums, Exceptions, Interfaces
├── Validator/             # Input validation
├── Infrastructure/Logger/ # PSR-3 compatible logging
├── Channel/               # Channel interface + implementations
│   ├── Email/             # EmailChannel + SmtpClientInterface
│   ├── SMS/               # SMSChannel + SmsGatewayInterface
│   └── Push/              # PushChannel + PushNotificationInterface
├── Router/                # Channel resolution
├── Service/               # NotificationDispatcher (orchestration)
└── Controller/            # HTTP endpoints
```

## Channel Validation Rules

| Channel | Recipient format          | Limits                    |
|---------|---------------------------|---------------------------|
| Email   | RFC 5322 email address    | HTML body sanitized       |
| SMS     | E.164 phone number        | Body truncated at 160 chars |
| Push    | Token `[A-Za-z0-9:_-]{16,255}` | Payload max 4 KB    |

## Implementing Real Providers

The service ships with mock providers for development. To use real providers, implement the corresponding interface and register it in `config/services.yaml`:

```php
// Example: real SMTP client
final class SymfonyMailerSmtpClient implements SmtpClientInterface
{
    public function sendEmail(string $to, string $subject, string $body): void
    {
        // your implementation
    }
}
```

```yaml
# config/services.yaml
App\Channel\Email\SmtpClientInterface: '@App\Channel\Email\SymfonyMailerSmtpClient'
```

Available interfaces:

- `App\Channel\Email\SmtpClientInterface` -- email delivery
- `App\Channel\SMS\SmsGatewayInterface` -- SMS delivery
- `App\Channel\Push\PushNotificationInterface` -- push delivery

## Development

```bash
# Run tests
composer test

# Static analysis (PHPStan level 8)
composer phpstan

# Both
composer check
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

[MIT](LICENSE)
