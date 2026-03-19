<?php

declare(strict_types=1);

namespace App\Contract\DTO;

final class NotificationResultDTO
{
    /** @param list<DeliveryResultDTO> $deliveries */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly array $deliveries,
    ) {
    }

    /** @return array{id: string, status: string, deliveries: list<array{channel: string, status: string, errorMessage: ?string, timestamp: ?string}>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'deliveries' => array_map(
                static fn (DeliveryResultDTO $delivery): array => $delivery->toArray(),
                $this->deliveries,
            ),
        ];
    }
}
