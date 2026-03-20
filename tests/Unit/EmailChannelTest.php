<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\Email\EmailChannel;
use App\Channel\Email\SmtpClientInterface;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\NullLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EmailChannelTest extends TestCase
{
    public function testSendDeliversEmailSuccessfully(): void
    {
        $client = $this->createMock(SmtpClientInterface::class);
        $client->expects(self::once())
            ->method('sendEmail')
            ->with('user@example.com', 'Subject', 'Body');

        $channel = new EmailChannel($client, new NullLogger());

        $result = $channel->send(new NotificationPayload('notif-1', 'user@example.com', 'Subject', 'Body'));

        self::assertSame('email', $result->channel);
        self::assertSame('sent', $result->status);
        self::assertNull($result->errorMessage);
    }

    public function testSendRejectsInvalidEmail(): void
    {
        $client = $this->createMock(SmtpClientInterface::class);
        $channel = new EmailChannel($client, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('valid email address');

        $channel->send(new NotificationPayload('notif-1', 'not-an-email', 'Subject', 'Body'));
    }

    public function testSendThrowsDeliveryExceptionOnSmtpFailure(): void
    {
        $client = $this->createMock(SmtpClientInterface::class);
        $client->method('sendEmail')->willThrowException(new RuntimeException('SMTP timeout'));

        $channel = new EmailChannel($client, new NullLogger());

        $this->expectException(DeliveryException::class);
        $this->expectExceptionMessage('Email delivery failed: SMTP timeout');

        $channel->send(new NotificationPayload('notif-1', 'user@example.com', 'Subject', 'Body'));
    }

    public function testSendReturnsCachedResultForDuplicateNotificationId(): void
    {
        $client = $this->createMock(SmtpClientInterface::class);
        $client->expects(self::once())->method('sendEmail');

        $channel = new EmailChannel($client, new NullLogger());
        $payload = new NotificationPayload('notif-dup', 'user@example.com', 'Subject', 'Body');

        $first = $channel->send($payload);
        $second = $channel->send($payload);

        self::assertSame($first, $second);
    }

    public function testSendSanitizesHtmlBody(): void
    {
        $sentBody = null;
        $client = $this->createMock(SmtpClientInterface::class);
        $client->expects(self::once())
            ->method('sendEmail')
            ->willReturnCallback(function (string $to, string $subject, string $body) use (&$sentBody): void {
                $sentBody = $body;
            });

        $channel = new EmailChannel($client, new NullLogger());
        $channel->send(new NotificationPayload(
            'notif-html',
            'user@example.com',
            'Subject',
            '<p>Hello</p><script>alert("xss")</script><strong>World</strong>',
            ['format' => 'html'],
        ));

        self::assertNotNull($sentBody);
        self::assertStringNotContainsString('<script>', $sentBody);
        self::assertStringContainsString('<p>Hello</p>', $sentBody);
        self::assertStringContainsString('<strong>', $sentBody);
    }

    public function testSendKeepsPlainTextBodyUntouched(): void
    {
        $body = 'Plain text with <no> tags stripped';

        $client = $this->createMock(SmtpClientInterface::class);
        $client->expects(self::once())
            ->method('sendEmail')
            ->with('user@example.com', 'Subject', $body);

        $channel = new EmailChannel($client, new NullLogger());
        $channel->send(new NotificationPayload('notif-text', 'user@example.com', 'Subject', $body));
    }

    public function testIsAvailableReturnsTrue(): void
    {
        $client = $this->createMock(SmtpClientInterface::class);
        $channel = new EmailChannel($client, new NullLogger());

        self::assertTrue($channel->isAvailable());
    }
}
