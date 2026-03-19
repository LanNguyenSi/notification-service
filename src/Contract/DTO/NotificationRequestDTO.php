<?php

declare(strict_types=1);

namespace App\Contract\DTO;

final class NotificationRequestDTO
{
    /**
     * @param list<string> $channels
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly string $recipient,
        public readonly array $channels,
        public readonly string $subject,
        public readonly string $body,
        public readonly ?array $metadata = null,
    ) {
    }
}
