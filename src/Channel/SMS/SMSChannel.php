<?php

declare(strict_types=1);

namespace App\Channel\SMS;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Enum\Channel;
use App\Contract\Enum\NotificationStatus;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\LoggerInterface;
use Throwable;

final class SMSChannel implements ChannelInterface
{
    /** @var array<string, DeliveryResultDTO> */
    private array $resultsByNotification = [];

    public function __construct(
        private readonly SmsGatewayInterface $gateway,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getName(): Channel
    {
        return Channel::SMS;
    }

    public function send(NotificationPayload $payload): DeliveryResultDTO
    {
        if (isset($this->resultsByNotification[$payload->notificationId])) {
            return $this->resultsByNotification[$payload->notificationId];
        }

        if (!preg_match('/^\+[1-9]\d{1,14}$/', $payload->recipient)) {
            throw new DeliveryException('Recipient must be a valid E.164 phone number for the SMS channel.');
        }

        $message = $payload->body;
        if (mb_strlen($message) > 160) {
            $this->logger->warning('SMS body exceeded 160 characters and was truncated.', ['notification_id' => $payload->notificationId, 'channel' => $this->getName()->value]);
            $message = mb_substr($message, 0, 160);
        }

        try {
            $this->gateway->sendSms($payload->recipient, $message);
        } catch (Throwable $exception) {
            throw new DeliveryException('SMS delivery failed: ' . $exception->getMessage(), 0, $exception);
        }

        $result = new DeliveryResultDTO(
            channel: $this->getName()->value,
            status: NotificationStatus::SENT->value,
            errorMessage: null,
            timestamp: gmdate(DATE_ATOM),
        );

        $this->logger->info('SMS delivered.', ['notification_id' => $payload->notificationId, 'channel' => $this->getName()->value]);

        return $this->resultsByNotification[$payload->notificationId] = $result;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
