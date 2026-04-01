<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\Exception\ValidationException;
use App\Infrastructure\Logger\NullLogger;
use App\Infrastructure\Persistence\InMemoryNotificationRepository;
use App\Service\NotificationDispatcher;
use App\Validator\NotificationValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotificationDispatcherExtendedTest extends TestCase
{
    public function testSendPropagatesValidationException(): void
    {
        $bus = new class () implements MessageBusInterface {
            /** @param array<mixed> $stamps */
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message);
            }
        };

        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new NullLogger(),
            new InMemoryNotificationRepository(),
            $bus,
        );

        $this->expectException(ValidationException::class);

        $dispatcher->send(new NotificationRequestDTO('test@example.com', ['invalid'], 'Hi', 'Body'));
    }

    public function testSendQueuesMultipleChannels(): void
    {
        $bus = new CollectingMessageBus();

        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new NullLogger(),
            new InMemoryNotificationRepository(),
            $bus,
        );

        $result = $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email', 'sms', 'push'], 'Hi', 'Body'));

        self::assertSame('queued', $result->status);
        self::assertCount(1, $bus->dispatched);

        $message = $bus->dispatched[0];
        self::assertInstanceOf(\App\Message\SendNotificationMessage::class, $message);
        self::assertSame(['email', 'sms', 'push'], $message->channels);
    }
}
