<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\SMS\SMSChannel;
use App\Channel\SMS\SmsGatewayInterface;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\NullLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SMSChannelTest extends TestCase
{
    public function testSendDeliversSmsSuccessfully(): void
    {
        $gateway = $this->createMock(SmsGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('sendSms')
            ->with('+4915112345678', 'Hello');

        $channel = new SMSChannel($gateway, new NullLogger());

        $result = $channel->send(new NotificationPayload('notif-1', '+4915112345678', 'Subject', 'Hello'));

        self::assertSame('sms', $result->channel);
        self::assertSame('sent', $result->status);
    }

    public function testSendRejectsInvalidPhoneNumber(): void
    {
        $gateway = $this->createMock(SmsGatewayInterface::class);
        $channel = new SMSChannel($gateway, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('valid E.164 phone number');

        $channel->send(new NotificationPayload('notif-1', '0151-12345', 'Subject', 'Body'));
    }

    public function testSendTruncatesBodyOver160Characters(): void
    {
        $longBody = str_repeat('A', 200);

        $gateway = $this->createMock(SmsGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('sendSms')
            ->with('+4915112345678', str_repeat('A', 160));

        $channel = new SMSChannel($gateway, new NullLogger());
        $channel->send(new NotificationPayload('notif-trunc', '+4915112345678', 'Subject', $longBody));
    }

    public function testSendThrowsDeliveryExceptionOnGatewayFailure(): void
    {
        $gateway = $this->createMock(SmsGatewayInterface::class);
        $gateway->method('sendSms')->willThrowException(new RuntimeException('Gateway unreachable'));

        $channel = new SMSChannel($gateway, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('SMS delivery failed: Gateway unreachable');

        $channel->send(new NotificationPayload('notif-1', '+4915112345678', 'Subject', 'Body'));
    }

    public function testSendReturnsCachedResultForDuplicateNotificationId(): void
    {
        $gateway = $this->createMock(SmsGatewayInterface::class);
        $gateway->expects(self::once())->method('sendSms');

        $channel = new SMSChannel($gateway, new NullLogger());
        $payload = new NotificationPayload('notif-dup', '+4915112345678', 'Subject', 'Body');

        $first = $channel->send($payload);
        $second = $channel->send($payload);

        self::assertSame($first, $second);
    }
}
