<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationShowTest extends WebTestCase
{
    public function testGetNotificationReturnsSentStatusAfterProcessing(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/v1/notifications', [
            'recipient' => 'user@example.com',
            'channels' => ['email'],
            'subject' => 'Welcome',
            'body' => 'Hello!',
        ], ['HTTP_X-API-Key' => 'test-api-key']);

        self::assertResponseStatusCodeSame(202);
        $postData = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $notificationId = $postData['id'];

        $client->request('GET', '/api/v1/notifications/' . $notificationId, [], [], [
            'HTTP_X-API-Key' => 'test-api-key',
        ]);

        self::assertResponseIsSuccessful();
        $getData = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($notificationId, $getData['id']);
        self::assertSame('sent', $getData['status']);
        self::assertSame('user@example.com', $getData['recipient']);
        self::assertSame('Welcome', $getData['subject']);
        self::assertCount(1, $getData['deliveries']);
    }

    public function testGetNonExistentNotificationReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/notifications/non-existent-id', [], [], [
            'HTTP_X-API-Key' => 'test-api-key',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testGetNotificationIncludesRequestIdHeader(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/notifications/some-id', [], [], [
            'HTTP_X-Request-ID' => 'custom-req-id',
            'HTTP_X-API-Key' => 'test-api-key',
        ]);

        self::assertResponseHasHeader('x-request-id');
        self::assertResponseHeaderSame('x-request-id', 'custom-req-id');
    }
}
