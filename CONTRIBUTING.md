# Contributing

Contributions are welcome! Here's how to get started.

## Setup

```bash
git clone https://github.com/pandora/notification-service.git
cd notification-service
composer install
```

## Development workflow

1. Create a branch from `main`
2. Make your changes
3. Run the checks: `composer check`
4. Open a pull request

## Code standards

- Follow PSR-12 coding style
- All classes must declare `strict_types=1`
- PHPStan level 8 must pass without errors
- Public methods need PHPDoc only when types alone don't convey the contract

## Testing

- Every new feature or bugfix needs tests
- Unit tests go in `tests/Unit/`, integration tests in `tests/Integration/`
- Mock all external dependencies in unit tests
- Run the full suite with `composer test`

## Adding a new channel

1. Create the provider interface in `src/Channel/YourChannel/`
2. Implement `ChannelInterface` in `src/Channel/YourChannel/YourChannel.php`
3. Add a mock provider for development
4. Register the channel in `config/services.yaml` with the `app.notification_channel` tag
5. Add the channel name to `src/Contract/Enum/Channel.php`
6. Add tests

## Reporting issues

Open an issue with a clear description of the problem and steps to reproduce it.
