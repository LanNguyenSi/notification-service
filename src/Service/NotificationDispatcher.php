<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;
use App\Contract\Enum\NotificationStatus;
use App\Contract\Interface\NotificationRepositoryInterface;
use App\Contract\Interface\NotificationServiceInterface;
use App\Contract\Interface\NotificationValidatorInterface;
use App\Infrastructure\Logger\LoggerInterface;
use App\Message\SendNotificationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotificationDispatcher implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly NotificationRepositoryInterface $repository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function send(NotificationRequestDTO $request): NotificationResultDTO
    {
        $validated = $this->validator->validate($request);
        $notificationId = Uuid::v4();

        $result = new NotificationResultDTO(
            id: $notificationId,
            status: NotificationStatus::QUEUED->value,
            deliveries: [],
        );

        $this->repository->save($result, $request);

        $this->messageBus->dispatch(new SendNotificationMessage(
            notificationId: $notificationId,
            recipient: $validated->recipient,
            channels: array_map(static fn ($channel) => $channel->value, $validated->channels),
            subject: $validated->subject,
            body: $validated->body,
            metadata: $validated->metadata ?: null,
        ));

        $this->logger->info('Notification queued.', ['notification_id' => $notificationId]);

        return $result;
    }
}
