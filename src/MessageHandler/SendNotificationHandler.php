<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Enum\NotificationStatus;
use App\Contract\Exception\DeliveryException;
use App\Contract\Interface\ChannelRouterInterface;
use App\Contract\Interface\NotificationRepositoryInterface;
use App\Infrastructure\Logger\LoggerInterface;
use App\Message\SendNotificationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendNotificationHandler
{
    public function __construct(
        private readonly ChannelRouterInterface $router,
        private readonly NotificationRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendNotificationMessage $message): void
    {
        $this->logger->info('Processing queued notification.', ['notification_id' => $message->notificationId]);

        $channels = $this->router->resolveChannels($message->channels);

        if ($channels === []) {
            $this->logger->error('No channels available for queued notification.', ['notification_id' => $message->notificationId]);
            $this->repository->updateStatus($message->notificationId, NotificationStatus::FAILED->value, []);

            return;
        }

        $payload = new NotificationPayload(
            notificationId: $message->notificationId,
            recipient: $message->recipient,
            subject: $message->subject,
            body: $message->body,
            metadata: $message->metadata ?? [],
        );

        $deliveries = [];
        foreach ($channels as $channel) {
            $this->logger->info('Sending notification.', [
                'notification_id' => $message->notificationId,
                'channel' => $channel->getName()->value,
            ]);

            try {
                $result = $channel->send($payload);
                $deliveries[] = $result->toArray();
            } catch (DeliveryException $exception) {
                $this->logger->error('Notification delivery failed.', [
                    'notification_id' => $message->notificationId,
                    'channel' => $channel->getName()->value,
                    'error' => $exception->getMessage(),
                ]);
                $deliveries[] = (new DeliveryResultDTO(
                    channel: $channel->getName()->value,
                    status: NotificationStatus::FAILED->value,
                    errorMessage: $exception->getMessage(),
                    timestamp: null,
                ))->toArray();
            }
        }

        $status = $this->resolveStatus($deliveries);
        $this->repository->updateStatus($message->notificationId, $status, $deliveries);

        $this->logger->info('Notification processing complete.', [
            'notification_id' => $message->notificationId,
            'status' => $status,
        ]);
    }

    /** @param list<array{channel: string, status: string, errorMessage: ?string, timestamp: ?string}> $deliveries */
    private function resolveStatus(array $deliveries): string
    {
        $successCount = count(array_filter(
            $deliveries,
            static fn (array $delivery): bool => $delivery['status'] === NotificationStatus::SENT->value,
        ));

        if ($successCount === count($deliveries)) {
            return NotificationStatus::SENT->value;
        }

        if ($successCount > 0) {
            return NotificationStatus::PARTIAL->value;
        }

        return NotificationStatus::FAILED->value;
    }
}
