<?php

declare(strict_types=1);

namespace App\Validator;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\ValidatedRequest;
use App\Contract\Enum\Channel;
use App\Contract\Exception\ValidationException;
use App\Contract\Interface\NotificationValidatorInterface;

final class NotificationValidator implements NotificationValidatorInterface
{
    public function validate(NotificationRequestDTO $request): ValidatedRequest
    {
        $subject = trim($request->subject);
        $body = trim($request->body);

        if ($subject === '') {
            throw new ValidationException('Subject must not be empty.');
        }

        if ($body === '') {
            throw new ValidationException('Body must not be empty.');
        }

        if (mb_strlen($subject) > 200) {
            throw new ValidationException('Subject must not exceed 200 characters.');
        }

        if (mb_strlen($body) > 5000) {
            throw new ValidationException('Body must not exceed 5000 characters.');
        }

        if ($request->channels === []) {
            throw new ValidationException('At least one delivery channel must be provided.');
        }

        $channels = [];
        foreach ($request->channels as $channelName) {
            $channel = Channel::tryFrom(strtolower(trim($channelName)));
            if ($channel === null) {
                throw new ValidationException(sprintf('Unsupported channel "%s". Supported channels: %s.', $channelName, implode(', ', Channel::values())));
            }

            $channels[$channel->value] = $channel;
        }

        if (isset($channels[Channel::EMAIL->value]) && !filter_var($request->recipient, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Recipient must be a valid email address when the email channel is used.');
        }

        return new ValidatedRequest(
            recipient: trim($request->recipient),
            channels: array_values($channels),
            subject: $subject,
            body: $body,
            metadata: $request->metadata ?? [],
        );
    }
}
