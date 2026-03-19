<?php

declare(strict_types=1);

namespace App\Contract\Interface;

use App\Channel\ChannelInterface;
use App\Contract\Exception\UnsupportedChannelException;

interface ChannelRouterInterface
{
    /**
     * @param list<string> $channelNames
     * @return list<ChannelInterface>
     * @throws UnsupportedChannelException
     */
    public function resolveChannels(array $channelNames): array;
}
