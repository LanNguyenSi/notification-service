<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\Enum\Channel;
use App\Contract\Exception\ValidationException;
use App\Validator\NotificationValidator;
use PHPUnit\Framework\TestCase;

final class NotificationValidatorExtendedTest extends TestCase
{
    private NotificationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new NotificationValidator();
    }

    public function testValidateRejectsEmptyBody(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Body must not be empty.');

        $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: 'Hello',
            body: '   ',
        ));
    }

    public function testValidateRejectsSubjectExceeding200Characters(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Subject must not exceed 200 characters.');

        $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: str_repeat('A', 201),
            body: 'Body',
        ));
    }

    public function testValidateRejectsBodyExceeding5000Characters(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Body must not exceed 5000 characters.');

        $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: 'Hello',
            body: str_repeat('A', 5001),
        ));
    }

    public function testValidateRejectsEmptyChannelsArray(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('At least one delivery channel must be provided.');

        $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: [],
            subject: 'Hello',
            body: 'World',
        ));
    }

    public function testValidateDeduplicatesChannels(): void
    {
        $validated = $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email', 'email'],
            subject: 'Hello',
            body: 'World',
        ));

        self::assertCount(1, $validated->channels);
        self::assertSame(Channel::EMAIL, $validated->channels[0]);
    }

    public function testValidateTrimsSubjectAndBody(): void
    {
        $validated = $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: '  Hello  ',
            body: '  World  ',
        ));

        self::assertSame('Hello', $validated->subject);
        self::assertSame('World', $validated->body);
    }

    public function testValidateAcceptsMultipleChannels(): void
    {
        $validated = $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email', 'sms', 'push'],
            subject: 'Hello',
            body: 'World',
        ));

        self::assertCount(3, $validated->channels);
    }

    public function testValidateDefaultsMetadataToEmptyArray(): void
    {
        $validated = $this->validator->validate(new NotificationRequestDTO(
            recipient: 'test@example.com',
            channels: ['email'],
            subject: 'Hello',
            body: 'World',
        ));

        self::assertSame([], $validated->metadata);
    }
}
