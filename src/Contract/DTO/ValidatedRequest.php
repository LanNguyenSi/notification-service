<?php

declare(strict_types=1);

namespace App\Contract\DTO;

use App\Contract\Enum\Channel;

final class ValidatedRequest
{
    /**
     * @param list<Channel> $channels
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $recipient,
        public readonly array $channels,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $metadata = [],
    ) {
    }
}
