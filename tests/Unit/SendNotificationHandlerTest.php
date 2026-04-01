<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;
use App\Contract\Enum\Channel;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\NullLogger;
use App\Infrastructure\Persistence\InMemoryNotificationRepository;
use App\Message\SendNotificationMessage;
use App\MessageHandler\SendNotificationHandler;
use App\Router\ChannelRouter;
use PHPUnit\Framework\TestCase;

final class SendNotificationHandlerTest extends TestCase
{
    public function testHandlerProcessesNotificationSuccessfully(): void
    {
        $repository = new InMemoryNotificationRepository();
        $this->seedRepository($repository, 'notif-1');

        $handler = new SendNotificationHandler(
            new ChannelRouter([new HandlerTestChannel(Channel::EMAIL)], new NullLogger()),
            $repository,
            new NullLogger(),
        );

        $handler(new SendNotificationMessage('notif-1', 'test@example.com', ['email'], 'Hi', 'Body'));

        $found = $repository->findById('notif-1');
        self::assertNotNull($found);
        self::assertSame('sent', $found['status']);
        self::assertCount(1, $found['deliveries']);
        self::assertSame('email', $found['deliveries'][0]['channel']);
        self::assertSame('sent', $found['deliveries'][0]['status']);
    }

    public function testHandlerSetsPartialStatusWhenOneChannelFails(): void
    {
        $repository = new InMemoryNotificationRepository();
        $this->seedRepository($repository, 'notif-2');

        $handler = new SendNotificationHandler(
            new ChannelRouter([
                new HandlerTestChannel(Channel::EMAIL),
                new HandlerTestChannel(Channel::PUSH, shouldFail: true),
            ], new NullLogger()),
            $repository,
            new NullLogger(),
        );

        $handler(new SendNotificationMessage('notif-2', 'test@example.com', ['email', 'push'], 'Hi', 'Body'));

        $found = $repository->findById('notif-2');
        self::assertNotNull($found);
        self::assertSame('partial', $found['status']);
    }

    public function testHandlerSetsFailedStatusWhenAllChannelsFail(): void
    {
        $repository = new InMemoryNotificationRepository();
        $this->seedRepository($repository, 'notif-3');

        $handler = new SendNotificationHandler(
            new ChannelRouter([new HandlerTestChannel(Channel::EMAIL, shouldFail: true)], new NullLogger()),
            $repository,
            new NullLogger(),
        );

        $handler(new SendNotificationMessage('notif-3', 'test@example.com', ['email'], 'Hi', 'Body'));

        $found = $repository->findById('notif-3');
        self::assertNotNull($found);
        self::assertSame('failed', $found['status']);
    }

    public function testHandlerSetsFailedStatusWhenNoChannelsAvailable(): void
    {
        $repository = new InMemoryNotificationRepository();
        $this->seedRepository($repository, 'notif-4');

        $handler = new SendNotificationHandler(
            new ChannelRouter([new HandlerTestChannel(Channel::EMAIL, available: false)], new NullLogger()),
            $repository,
            new NullLogger(),
        );

        $handler(new SendNotificationMessage('notif-4', 'test@example.com', ['email'], 'Hi', 'Body'));

        $found = $repository->findById('notif-4');
        self::assertNotNull($found);
        self::assertSame('failed', $found['status']);
    }

    private function seedRepository(InMemoryNotificationRepository $repository, string $id): void
    {
        $request = new NotificationRequestDTO('test@example.com', ['email'], 'Hi', 'Body');
        $result = new NotificationResultDTO($id, 'queued', []);
        $repository->save($result, $request);
    }
}

/** @internal */
final class HandlerTestChannel implements ChannelInterface
{
    public function __construct(
        private readonly Channel $name,
        private readonly bool $shouldFail = false,
        private readonly bool $available = true,
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
        return $this->available;
    }
}
