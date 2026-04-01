<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Contract\DTO\NotificationRequestDTO;
use App\Infrastructure\Logger\NullLogger;
use App\Infrastructure\Persistence\InMemoryNotificationRepository;
use App\Message\SendNotificationMessage;
use App\Service\NotificationDispatcher;
use App\Validator\NotificationValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotificationDispatcherTest extends TestCase
{
    public function testSendQueuesNotificationAndReturnsQueuedStatus(): void
    {
        $bus = new CollectingMessageBus();
        $repository = new InMemoryNotificationRepository();

        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new NullLogger(),
            $repository,
            $bus,
        );

        $result = $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email'], 'Hi', 'Body'));

        self::assertSame('queued', $result->status);
        self::assertSame([], $result->deliveries);
        self::assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $result->id);
        self::assertCount(1, $bus->dispatched);
        self::assertInstanceOf(SendNotificationMessage::class, $bus->dispatched[0]);
        self::assertSame($result->id, $bus->dispatched[0]->notificationId);
        self::assertSame('test@example.com', $bus->dispatched[0]->recipient);
        self::assertSame(['email'], $bus->dispatched[0]->channels);
    }

    public function testSendPersistsNotificationWithQueuedStatus(): void
    {
        $bus = new CollectingMessageBus();
        $repository = new InMemoryNotificationRepository();

        $dispatcher = new NotificationDispatcher(
            new NotificationValidator(),
            new NullLogger(),
            $repository,
            $bus,
        );

        $result = $dispatcher->send(new NotificationRequestDTO('test@example.com', ['email'], 'Hi', 'Body'));

        $found = $repository->findById($result->id);
        self::assertNotNull($found);
        self::assertSame('queued', $found['status']);
    }
}

/** @internal */
final class CollectingMessageBus implements MessageBusInterface
{
    /** @var list<object> */
    public array $dispatched = [];

    /** @param array<mixed> $stamps */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;

        return new Envelope($message);
    }
}
