<?php

declare(strict_types=1);

namespace App\Contract\Interface;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;

interface NotificationRepositoryInterface
{
    public function save(NotificationResultDTO $result, NotificationRequestDTO $request): void;

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array;

    /** @param list<array{channel: string, status: string, errorMessage: ?string, timestamp: ?string}> $deliveries */
    public function updateStatus(string $id, string $status, array $deliveries): void;
}
