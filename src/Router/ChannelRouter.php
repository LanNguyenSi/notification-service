<?php

declare(strict_types=1);

namespace App\Router;

use App\Channel\ChannelInterface;
use App\Contract\Enum\Channel;
use App\Contract\Exception\UnsupportedChannelException;
use App\Contract\Interface\ChannelRouterInterface;
use App\Infrastructure\Logger\LoggerInterface;

final class ChannelRouter implements ChannelRouterInterface
{
    /** @var array<string, ChannelInterface> */
    private array $channelMap = [];

    /** @param iterable<ChannelInterface> $channels */
    public function __construct(iterable $channels, private readonly LoggerInterface $logger)
    {
        foreach ($channels as $channel) {
            $this->channelMap[$channel->getName()->value] = $channel;
        }
    }

    /**
     * @param list<string> $channelNames
     * @return list<ChannelInterface>
     */
    public function resolveChannels(array $channelNames): array
    {
        $resolved = [];
        $unsupported = [];

        foreach ($channelNames as $channelName) {
            $normalized = strtolower($channelName);
            $channelEnum = Channel::tryFrom($normalized);
            if ($channelEnum === null || !isset($this->channelMap[$normalized])) {
                $unsupported[] = $channelName;
                continue;
            }

            $channel = $this->channelMap[$normalized];
            if (!$channel->isAvailable()) {
                $this->logger->warning('Channel is unavailable and was filtered out.', ['channel' => $normalized]);
                continue;
            }

            $resolved[$normalized] = $channel;
        }

        if ($unsupported !== []) {
            throw new UnsupportedChannelException(sprintf('Unsupported channel implementation(s): %s.', implode(', ', $unsupported)));
        }

        return array_values($resolved);
    }
}
