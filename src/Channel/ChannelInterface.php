<?php

declare(strict_types=1);

namespace App\Channel;

use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Enum\Channel;
use App\Contract\Exception\DeliveryException;

interface ChannelInterface
{
    public function getName(): Channel;

    public function send(NotificationPayload $payload): DeliveryResultDTO;

    public function isAvailable(): bool;
}
