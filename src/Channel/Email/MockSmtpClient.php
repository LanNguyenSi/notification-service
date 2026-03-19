<?php

declare(strict_types=1);

namespace App\Channel\Email;

final class MockSmtpClient implements SmtpClientInterface
{
    /** @var list<array{to: string, subject: string, body: string}> */
    private array $messages = [];

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $this->messages[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
    }

    /** @return list<array{to: string, subject: string, body: string}> */
    public function messages(): array
    {
        return $this->messages;
    }
}
