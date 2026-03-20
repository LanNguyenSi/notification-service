<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\Push\PushChannel;
use App\Channel\Push\PushNotificationInterface;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\NullLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PushChannelTest extends TestCase
{
    private const VALID_TOKEN = 'abcdefghijklmnop'; // 16 chars minimum

    public function testSendDeliversPushSuccessfully(): void
    {
        $provider = $this->createMock(PushNotificationInterface::class);
        $provider->expects(self::once())
            ->method('sendPush')
            ->with(self::VALID_TOKEN, 'Title', 'Body');

        $channel = new PushChannel($provider, new NullLogger());

        $result = $channel->send(new NotificationPayload('notif-1', self::VALID_TOKEN, 'Title', 'Body'));

        self::assertSame('push', $result->channel);
        self::assertSame('sent', $result->status);
    }

    public function testSendRejectsInvalidPushToken(): void
    {
        $provider = $this->createMock(PushNotificationInterface::class);
        $channel = new PushChannel($provider, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('valid push token');

        $channel->send(new NotificationPayload('notif-1', 'short', 'Title', 'Body'));
    }

    public function testSendRejectsPayloadExceeding4KB(): void
    {
        $provider = $this->createMock(PushNotificationInterface::class);
        $channel = new PushChannel($provider, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('4KB limit');

        $channel->send(new NotificationPayload('notif-1', self::VALID_TOKEN, 'Title', str_repeat('X', 5000)));
    }

    public function testSendThrowsDeliveryExceptionOnProviderFailure(): void
    {
        $provider = $this->createMock(PushNotificationInterface::class);
        $provider->method('sendPush')->willThrowException(new RuntimeException('FCM error'));

        $channel = new PushChannel($provider, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('Push delivery failed: FCM error');

        $channel->send(new NotificationPayload('notif-1', self::VALID_TOKEN, 'Title', 'Body'));
    }

    public function testSendReturnsCachedResultForDuplicateNotificationId(): void
    {
        $provider = $this->createMock(PushNotificationInterface::class);
        $provider->expects(self::once())->method('sendPush');

        $channel = new PushChannel($provider, new NullLogger());
        $payload = new NotificationPayload('notif-dup', self::VALID_TOKEN, 'Title', 'Body');

        $first = $channel->send($payload);
        $second = $channel->send($payload);

        self::assertSame($first, $second);
    }
}
