<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationControllerTest extends WebTestCase
{
    public function testNotificationEndpointReturnsAcceptedPayload(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Welcome',
            'body' => 'Welcome to our service!',
        ], ['HTTP_X-API-Key' => 'test-api-key']);

        self::assertResponseStatusCodeSame(202);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertResponseHasHeader('x-request-id');

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('status', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $data['id']);
    }

    public function testNotificationEndpointReturnsProblemDetailsForValidationErrors(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'invalid',
            'channels' => ['email'],
            'subject' => '',
            'body' => '',
        ], ['HTTP_X-API-Key' => 'test-api-key']);

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        self::assertResponseHasHeader('x-request-id');
    }

    public function testHealthEndpointReturnsOkWithoutApiKey(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ok"}', (string) $client->getResponse()->getContent());
    }
}
