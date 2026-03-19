<?php

declare(strict_types=1);

namespace App\Contract\DTO;

final class DeliveryResultDTO
{
    public function __construct(
        public readonly string $channel,
        public readonly string $status,
        public readonly ?string $errorMessage = null,
        public readonly ?string $timestamp = null,
    ) {
    }

    /** @return array{channel: string, status: string, errorMessage: ?string, timestamp: ?string} */
    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'status' => $this->status,
            'errorMessage' => $this->errorMessage,
            'timestamp' => $this->timestamp,
        ];
    }
}
