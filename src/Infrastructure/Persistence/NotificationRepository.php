<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;
use App\Contract\Interface\NotificationRepositoryInterface;

final class NotificationRepository implements NotificationRepositoryInterface
{
    private readonly string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function save(NotificationResultDTO $result, NotificationRequestDTO $request): void
    {
        $now = gmdate(DATE_ATOM);

        $data = [
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

        file_put_contents($this->filePath($result->id), json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        $path = $this->filePath($id);

        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        /** @var array<string, mixed> */
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param list<array{channel: string, status: string, errorMessage: ?string, timestamp: ?string}> $deliveries */
    public function updateStatus(string $id, string $status, array $deliveries): void
    {
        $existing = $this->findById($id);

        if ($existing === null) {
            return;
        }

        $existing['status'] = $status;
        $existing['deliveries'] = $deliveries;
        $existing['updated_at'] = gmdate(DATE_ATOM);

        file_put_contents($this->filePath($id), json_encode($existing, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    private function filePath(string $id): string
    {
        return $this->storagePath . '/' . $id . '.json';
    }
}
