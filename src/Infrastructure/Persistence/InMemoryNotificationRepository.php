<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;
use App\Contract\Interface\NotificationRepositoryInterface;

final class InMemoryNotificationRepository implements NotificationRepositoryInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $notifications = [];

    public function save(NotificationResultDTO $result, NotificationRequestDTO $request): void
    {
        $now = gmdate(DATE_ATOM);

        $this->notifications[$result->id] = [
            'id' => $result->id,
            'recipient' => $request->recipient,
            'subject' => $request->subject,
            'body' => $request->body,
            'channels' => $request->channels,
            'metadata' => $request->metadata,
            'status' => $result->status,
            'deliveries' => array_map(static fn ($d) => $d->toArray(), $result->deliveries),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        return $this->notifications[$id] ?? null;
    }

    /** @param list<array{channel: string, status: string, errorMessage: ?string, timestamp: ?string}> $deliveries */
    public function updateStatus(string $id, string $status, array $deliveries): void
    {
        if (!isset($this->notifications[$id])) {
            return;
        }

        $this->notifications[$id]['status'] = $status;
        $this->notifications[$id]['deliveries'] = $deliveries;
        $this->notifications[$id]['updated_at'] = gmdate(DATE_ATOM);
    }
}
