<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;
use App\Infrastructure\Persistence\NotificationRepository;
use PHPUnit\Framework\TestCase;

final class NotificationRepositoryTest extends TestCase
{
    private NotificationRepository $repository;
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/notification-test-' . uniqid('', true);
        $this->repository = new NotificationRepository($this->storagePath);
    }

    protected function tearDown(): void
    {
        $files = glob($this->storagePath . '/*.json');
        if ($files !== false) {
            array_map('unlink', $files);
        }

        if (is_dir($this->storagePath)) {
            rmdir($this->storagePath);
        }
    }

    public function testSaveAndFindById(): void
    {
        $request = new NotificationRequestDTO('user@example.com', ['email'], 'Welcome', 'Hello!');
        $result = new NotificationResultDTO('test-id-123', 'sent', [
            new DeliveryResultDTO('email', 'sent', null, '2026-01-01T00:00:00Z'),
        ]);

        $this->repository->save($result, $request);

        $found = $this->repository->findById('test-id-123');

        self::assertNotNull($found);
        self::assertSame('test-id-123', $found['id']);
        self::assertSame('user@example.com', $found['recipient']);
        self::assertSame('Welcome', $found['subject']);
        self::assertSame('sent', $found['status']);
        self::assertCount(1, $found['deliveries']);
        self::assertSame('email', $found['deliveries'][0]['channel']);
        self::assertSame('sent', $found['deliveries'][0]['status']);
    }

    public function testFindByIdReturnsNullForNonExistentNotification(): void
    {
        self::assertNull($this->repository->findById('non-existent'));
    }

    public function testUpdateStatus(): void
    {
        $request = new NotificationRequestDTO('user@example.com', ['email'], 'Welcome', 'Hello!');
        $result = new NotificationResultDTO('test-id-456', 'queued', []);

        $this->repository->save($result, $request);

        $this->repository->updateStatus('test-id-456', 'sent', [
            ['channel' => 'email', 'status' => 'sent', 'errorMessage' => null, 'timestamp' => '2026-01-01T00:00:00Z'],
        ]);

        $found = $this->repository->findById('test-id-456');

        self::assertNotNull($found);
        self::assertSame('sent', $found['status']);
        self::assertCount(1, $found['deliveries']);
        self::assertSame('email', $found['deliveries'][0]['channel']);
    }

    public function testSaveWithMetadata(): void
    {
        $request = new NotificationRequestDTO('user@example.com', ['email'], 'Welcome', 'Hello!', ['format' => 'html']);
        $result = new NotificationResultDTO('test-id-789', 'sent', [
            new DeliveryResultDTO('email', 'sent', null, '2026-01-01T00:00:00Z'),
        ]);

        $this->repository->save($result, $request);

        $found = $this->repository->findById('test-id-789');

        self::assertNotNull($found);
        self::assertSame(['format' => 'html'], $found['metadata']);
    }
}
