<?php

declare(strict_types=1);

namespace App\Channel\Push;

interface PushNotificationInterface
{
    public function sendPush(string $deviceToken, string $title, string $body): void;
}
