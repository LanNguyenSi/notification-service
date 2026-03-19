<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Enum\Channel;
use App\Contract\Exception\UnsupportedChannelException;
use App\Infrastructure\Logger\NullLogger;
use App\Router\ChannelRouter;
use PHPUnit\Framework\TestCase;

final class ChannelRouterTest extends TestCase
{
    public function testResolveChannelsReturnsAvailableChannels(): void
    {
        $router = new ChannelRouter([
            new TestChannel(Channel::EMAIL, true),
            new TestChannel(Channel::SMS, false),
        ], new NullLogger());

        $resolved = $router->resolveChannels(['email', 'sms']);

        self::assertCount(1, $resolved);
        self::assertSame(Channel::EMAIL, $resolved[0]->getName());
    }

    public function testResolveChannelsRejectsUnsupportedChannel(): void
    {
        $router = new ChannelRouter([new TestChannel(Channel::EMAIL, true)], new NullLogger());

        $this->expectException(UnsupportedChannelException::class);

        $router->resolveChannels(['push']);
    }
}

final class TestChannel implements ChannelInterface
{
    public function __construct(
        private readonly Channel $name,
        private readonly bool $available,
    ) {
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
        return $this->available;
    }
}
