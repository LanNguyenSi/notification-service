<?php

declare(strict_types=1);

namespace App\Channel\Email;

use App\Channel\ChannelInterface;
use App\Contract\DTO\DeliveryResultDTO;
use App\Contract\DTO\NotificationPayload;
use App\Contract\Enum\Channel;
use App\Contract\Enum\NotificationStatus;
use App\Contract\Exception\DeliveryException;
use App\Infrastructure\Logger\LoggerInterface;
use Throwable;

final class EmailChannel implements ChannelInterface
{
    /** @var array<string, DeliveryResultDTO> */
    private array $resultsByNotification = [];

    public function __construct(
        private readonly SmtpClientInterface $smtpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getName(): Channel
    {
        return Channel::EMAIL;
    }

    public function send(NotificationPayload $payload): DeliveryResultDTO
    {
        if (isset($this->resultsByNotification[$payload->notificationId])) {
            return $this->resultsByNotification[$payload->notificationId];
        }

        if (!filter_var($payload->recipient, FILTER_VALIDATE_EMAIL)) {
            throw new DeliveryException('Recipient must be a valid email address for the email channel.');
        }

        $body = $this->sanitizeBody($payload);

        try {
            $this->smtpClient->sendEmail($payload->recipient, $payload->subject, $body);
        } catch (Throwable $exception) {
            throw new DeliveryException('Email delivery failed: ' . $exception->getMessage(), 0, $exception);
        }

        $result = new DeliveryResultDTO(
            channel: $this->getName()->value,
            status: NotificationStatus::SENT->value,
            errorMessage: null,
            timestamp: gmdate(DATE_ATOM),
        );

        $this->logger->info('Email delivered.', ['notification_id' => $payload->notificationId, 'channel' => $this->getName()->value]);

        return $this->resultsByNotification[$payload->notificationId] = $result;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    private function sanitizeBody(NotificationPayload $payload): string
    {
        $format = $payload->metadata['format'] ?? 'text';
        if ($format === 'html') {
            return strip_tags($payload->body, '<p><br><strong><em><ul><ol><li><a>');
        }

        return $payload->body;
    }
}
