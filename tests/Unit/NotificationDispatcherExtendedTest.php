<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\Enum\Channel;
use App\Contract\Exception\DeliveryException;
use App\Contract\Exception\NoAvailableChannelException;
use App\Contract\Exception\ValidationException;
use App\Infrastructure\Logger\NullLogger;
use App\Router\ChannelRouter;
use App\Service\NotificationDispatcher;
use App\Validator\NotificationValidator;
use PHPUnit\Framework\TestCase;

final class NotificationDispatcherExtendedTest extends TestCase
{
    public function testSendReturnsFailedStatusWhenAllChannelsFail(): void
    {
        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new ChannelRouter([new FailingChannel(Channel::EMAIL)], new NullLogger()),
            new NullLogger(),
        );

        $result = $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email'], 'Hi', 'Body'));

        self::assertSame('failed', $result->status);
        self::assertCount(1, $result->deliveries);
        self::assertSame('failed', $result->deliveries[0]->status);
        self::assertNotNull($result->deliveries[0]->errorMessage);
    }

    public function testSendThrowsNoAvailableChannelExceptionWhenAllChannelsUnavailable(): void
    {
        $channel = new UnavailableChannel(Channel::EMAIL);
        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new ChannelRouter([$channel], new NullLogger()),
            new NullLogger(),
        );

        $this->expectException(NoAvailableChannelException::class);

        $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email'], 'Hi', 'Body'));
    }

    public function testSendPropagatesValidationException(): void
    {
        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new ChannelRouter([], new NullLogger()),
            new NullLogger(),
        );

        $this->expectException(ValidationException::class);

        $dispatcher->send(new NotificationRequestDTO('test@example.com', ['invalid'], 'Hi', 'Body'));
    }
}

/** @internal */
final class FailingChannel implements ChannelInterface
{
    public function __construct(private readonly Channel $name)
    {
    }

    public function getName(): Channel
    {
        return $this->name;
    }

    public function send(NotificationPayload $payload): DeliveryResultDTO
    {
        throw new DeliveryException('Channel unavailable');
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

/** @internal */
final class UnavailableChannel implements ChannelInterface
{
    public function __construct(private readonly Channel $name)
    {
    }

    public function getName(): Channel
    {
        return $this->name;
    }

    public function send(NotificationPayload $payload): DeliveryResultDTO
    {
        return new DeliveryResultDTO($this->name->value, 'sent');
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
