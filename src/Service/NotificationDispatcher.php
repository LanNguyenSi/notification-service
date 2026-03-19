<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;
use App\Contract\Enum\NotificationStatus;
use App\Contract\Exception\DeliveryException;
use App\Contract\Exception\NoAvailableChannelException;
use App\Contract\Interface\ChannelRouterInterface;
use App\Contract\Interface\NotificationServiceInterface;
use App\Contract\Interface\NotificationValidatorInterface;
use App\Infrastructure\Logger\LoggerInterface;

final class NotificationDispatcher implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationValidatorInterface $validator,
        private readonly ChannelRouterInterface $router,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(NotificationRequestDTO $request): NotificationResultDTO
    {
        $validated = $this->validator->validate($request);
        $notificationId = Uuid::v4();

        $channels = $this->router->resolveChannels(array_map(static fn ($channel) => $channel->value, $validated->channels));
        if ($channels === []) {
            throw new NoAvailableChannelException('No channels are currently available.');
        }

        $payload = new NotificationPayload(
            notificationId: $notificationId,
            recipient: $validated->recipient,
            subject: $validated->subject,
            body: $validated->body,
            metadata: $validated->metadata,
        );

        $deliveries = [];
        foreach ($channels as $channel) {
            $this->logger->info('Sending notification.', ['notification_id' => $notificationId, 'channel' => $channel->getName()->value]);

            try {
                $deliveries[] = $channel->send($payload);
            } catch (DeliveryException $exception) {
                $this->logger->error('Notification delivery failed.', ['notification_id' => $notificationId, 'channel' => $channel->getName()->value, 'error' => $exception->getMessage()]);
                $deliveries[] = new DeliveryResultDTO(
                    channel: $channel->getName()->value,
                    status: NotificationStatus::FAILED->value,
                    errorMessage: $exception->getMessage(),
                    timestamp: null,
                );
            }
        }

        return new NotificationResultDTO(
            id: $notificationId,
            status: $this->resolveStatus($deliveries),
            deliveries: $deliveries,
        );
    }

    /** @param list<DeliveryResultDTO> $deliveries */
    private function resolveStatus(array $deliveries): string
    {
        $successCount = count(array_filter($deliveries, static fn (DeliveryResultDTO $delivery): bool => $delivery->status === NotificationStatus::SENT->value));

        if ($successCount === count($deliveries)) {
            return NotificationStatus::SENT->value;
        }

        if ($successCount > 0) {
            return NotificationStatus::PARTIAL->value;
        }

        return NotificationStatus::FAILED->value;
    }
}
