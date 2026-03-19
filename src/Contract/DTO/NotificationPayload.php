<?php

declare(strict_types=1);

namespace App\Contract\DTO;

final class NotificationPayload
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly string $notificationId,
        public readonly string $recipient,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $metadata = [],
    ) {
    }
}
