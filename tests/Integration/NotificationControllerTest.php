<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationControllerTest extends WebTestCase
{
    public function testNotificationEndpointReturnsSuccessfulPayload(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Welcome',
            'body' => 'Welcome to our service!',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertResponseHasHeader('x-request-id');

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('sent', $data['status']);
        self::assertSame('email', $data['deliveries'][0]['channel']);
        self::assertSame('sent', $data['deliveries'][0]['status']);
    }

    public function testNotificationEndpointReturnsProblemDetailsForValidationErrors(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'invalid',
            'channels' => ['email'],
            'subject' => '',
            'body' => '',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        self::assertResponseHasHeader('x-request-id');
    }

    public function testHealthEndpointReturnsOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ok"}', (string) $client->getResponse()->getContent());
    }
}
