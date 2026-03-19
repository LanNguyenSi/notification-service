<?php

declare(strict_types=1);

namespace App\Channel\SMS;

final class MockSmsGateway implements SmsGatewayInterface
{
    /** @var list<array{phoneNumber: string, message: string}> */
    private array $messages = [];

    public function sendSms(string $phoneNumber, string $message): void
    {
        $this->messages[] = ['phoneNumber' => $phoneNumber, 'message' => $message];
    }

    /** @return list<array{phoneNumber: string, message: string}> */
    public function messages(): array
    {
        return $this->messages;
    }
}
