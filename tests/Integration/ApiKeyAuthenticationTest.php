<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiKeyAuthenticationTest extends WebTestCase
{
    public function testRequestWithoutApiKeyReturns401(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Test',
            'body' => 'Test body',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Authentication required', $data['title']);
    }

    public function testRequestWithInvalidApiKeyReturns401(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Test',
            'body' => 'Test body',
        ], ['HTTP_X-API-Key' => 'wrong-key']);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testRequestWithValidApiKeyIsAccepted(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Test',
            'body' => 'Test body',
        ], ['HTTP_X-API-Key' => 'test-api-key']);

        self::assertResponseStatusCodeSame(202);
    }

    public function testHealthEndpointDoesNotRequireApiKey(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ok"}', (string) $client->getResponse()->getContent());
    }

    public function testGetNotificationWithoutApiKeyReturns401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/notifications/some-id');

        self::assertResponseStatusCodeSame(401);
    }
}
