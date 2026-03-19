<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\Enum\Channel;
use App\Contract\Exception\ValidationException;
use App\Validator\NotificationValidator;
use PHPUnit\Framework\TestCase;

final class NotificationValidatorTest extends TestCase
{
    public function testValidateReturnsValidatedRequestForEmailRequest(): void
    {
        $validator = new NotificationValidator();

        $validated = $validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: 'Hello',
            body: 'World',
            metadata: ['format' => 'text'],
        ));

        self::assertSame('test@example.com', $validated->recipient);
        self::assertSame([Channel::EMAIL], $validated->channels);
        self::assertSame('Hello', $validated->subject);
        self::assertSame('World', $validated->body);
        self::assertSame(['format' => 'text'], $validated->metadata);
    }

    public function testValidateRejectsInvalidEmailForEmailChannel(): void
    {
        $validator = new NotificationValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Recipient must be a valid email address when the email channel is used.');

        $validator->validate(new NotificationRequestDTO(
            recipient: 'invalid-recipient',
            channels: ['email'],
            subject: 'Hello',
            body: 'World',
        ));
    }

    public function testValidateRejectsUnsupportedChannel(): void
    {
        $validator = new NotificationValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unsupported channel "fax".');

        $validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['fax'],
            subject: 'Hello',
            body: 'World',
        ));
    }

    public function testValidateRejectsEmptySubject(): void
    {
        $validator = new NotificationValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Subject must not be empty.');

        $validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: ' ',
            body: 'World',
        ));
    }
}
