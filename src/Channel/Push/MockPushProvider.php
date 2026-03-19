<?php

declare(strict_types=1);

namespace App\Channel\Push;

final class MockPushProvider implements PushNotificationInterface
{
    /** @var list<array{deviceToken: string, title: string, body: string}> */
    private array $messages = [];

    public function sendPush(string $deviceToken, string $title, string $body): void
    {
        $this->messages[] = ['deviceToken' => $deviceToken, 'title' => $title, 'body' => $body];
    }

    /** @return list<array{deviceToken: string, title: string, body: string}> */
    public function messages(): array
    {
        return $this->messages;
    }
}
