<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationControllerExtendedTest extends WebTestCase
{
    public function testNotificationEndpointReturnsErrorForEmptyBody(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/notifications', [], [], ['CONTENT_TYPE' => 'application/json'], '');

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testNotificationEndpointReturnsErrorForInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/notifications', [], [], ['CONTENT_TYPE' => 'application/json'], '{invalid}');

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testNotificationEndpointReturnsErrorForMissingFields(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testNotificationEndpointAcceptsMultipleChannels(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email', 'sms', 'push'],
            'subject' => 'Multi',
            'body' => 'Multi-channel test',
        ]);

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(3, $data['deliveries']);
    }

    public function testNotificationEndpointPreservesCustomRequestId(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Hello',
            'body' => 'World',
        ], ['HTTP_X-Request-ID' => 'custom-request-id-123']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('x-request-id', 'custom-request-id-123');
    }

    public function testNotificationEndpointRejectsGetMethod(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/notifications');

        self::assertResponseStatusCodeSame(405);
    }
}
