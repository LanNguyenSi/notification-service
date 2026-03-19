<?php

declare(strict_types=1);

namespace App\Channel\Push;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Enum\Channel;
use App\Contract\Enum\NotificationStatus;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\LoggerInterface;
use Throwable;

final class PushChannel implements ChannelInterface
{
    /** @var array<string, DeliveryResultDTO> */
    private array $resultsByNotification = [];

    public function __construct(
        private readonly PushNotificationInterface $provider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getName(): Channel
    {
        return Channel::PUSH;
    }

    public function send(NotificationPayload $payload): DeliveryResultDTO
    {
        if (isset($this->resultsByNotification[$payload->notificationId])) {
            return $this->resultsByNotification[$payload->notificationId];
        }

        if (!preg_match('/^[A-Za-z0-9:_\-]{16,255}$/', $payload->recipient)) {
            throw new DeliveryException('Recipient must be a valid push token for the push channel.');
        }

        $bodySize = strlen((string) json_encode(['title' => $payload->subject, 'body' => $payload->body], JSON_THROW_ON_ERROR));
        if ($bodySize > 4096) {
            throw new DeliveryException('Push payload exceeds the 4KB limit.');
        }

        try {
            $this->provider->sendPush($payload->recipient, $payload->subject, $payload->body);
        } catch (Throwable $exception) {
            throw new DeliveryException('Push delivery failed: ' . $exception->getMessage(), 0, $exception);
        }

        $result = new DeliveryResultDTO(
            channel: $this->getName()->value,
            status: NotificationStatus::SENT->value,
            errorMessage: null,
            timestamp: gmdate(DATE_ATOM),
        );

        $this->logger->info('Push notification delivered.', ['notification_id' => $payload->notificationId, 'channel' => $this->getName()->value]);

        return $this->resultsByNotification[$payload->notificationId] = $result;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
