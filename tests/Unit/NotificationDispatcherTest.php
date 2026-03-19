<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\Enum\Channel;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\NullLogger;
use App\Router\ChannelRouter;
use App\Service\NotificationDispatcher;
use App\Validator\NotificationValidator;
use PHPUnit\Framework\TestCase;

final class NotificationDispatcherTest extends TestCase
{
    public function testSendReturnsSentStatusWhenAllChannelsSucceed(): void
    {
        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new ChannelRouter([new DispatchTestChannel(Channel::EMAIL)], new NullLogger()),
            new NullLogger(),
        );

        $result = $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email'], 'Hi', 'Body'));

        self::assertSame('sent', $result->status);
        self::assertCount(1, $result->deliveries);
        self::assertSame('sent', $result->deliveries[0]->status);
        self::assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $result->id);
    }

    public function testSendReturnsPartialStatusWhenOneChannelFails(): void
    {
        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new ChannelRouter([
                new DispatchTestChannel(Channel::EMAIL),
                new DispatchTestChannel(Channel::PUSH, true),
            ], new NullLogger()),
            new NullLogger(),
        );

        $result = $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email', 'push'], 'Hi', 'Body'));

        self::assertSame('partial', $result->status);
        self::assertSame('sent', $result->deliveries[0]->status);
        self::assertSame('failed', $result->deliveries[1]->status);
    }
}

final class DispatchTestChannel implements ChannelInterface
{
    public function __construct(
        private readonly Channel $name,
        private readonly bool $shouldFail = false,
    ) {
    }

    public function getName(): Channel
    {
        return $this->name;
    }

    public function send(NotificationPayload $payload): DeliveryResultDTO
    {
        if ($this->shouldFail) {
            throw new DeliveryException('forced failure');
        }

        return new DeliveryResultDTO($this->name->value, 'sent', null, '2026-02-01T14:30:00Z');
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
