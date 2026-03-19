# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.1.0] - 2026-03-19

### Added

- Multi-channel notification delivery (email, SMS, push)
- `POST /api/v1/notifications` endpoint with JSON request/response
- `GET /health` endpoint
- Input validation (email format, E.164 phone numbers, push tokens, length limits)
- RFC 7807 problem details for error responses
- X-Request-ID header on all responses
- Channel router with availability filtering
- Partial failure handling (per-channel status reporting)
- Idempotent channel delivery
- Mock providers for all channels (development/testing)
- PHPStan level 8 static analysis
- Unit and integration test suite
