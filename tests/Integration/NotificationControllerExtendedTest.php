<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationControllerExtendedTest extends WebTestCase
{
    public function testNotificationEndpointReturnsErrorForEmptyBody(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/notifications', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-API-Key' => 'test-api-key',
        ], '');

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testNotificationEndpointReturnsErrorForInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/notifications', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-API-Key' => 'test-api-key',
        ], '{invalid}');

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testNotificationEndpointReturnsErrorForMissingFields(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
        ], ['HTTP_X-API-Key' => 'test-api-key']);

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
        ], ['HTTP_X-API-Key' => 'test-api-key']);

        self::assertResponseStatusCodeSame(202);
    }

    public function testNotificationEndpointPreservesCustomRequestId(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Hello',
            'body' => 'World',
        ], [
            'HTTP_X-Request-ID' => 'custom-request-id-123',
            'HTTP_X-API-Key' => 'test-api-key',
        ]);

        self::assertResponseStatusCodeSame(202);
        self::assertResponseHeaderSame('x-request-id', 'custom-request-id-123');
    }

    public function testNotificationEndpointRejectsGetMethod(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/notifications', [], [], [
            'HTTP_X-API-Key' => 'test-api-key',
        ]);

        self::assertResponseStatusCodeSame(405);
    }
}
