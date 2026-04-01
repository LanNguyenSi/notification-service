<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Security\ApiKeyAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiKeyAuthenticatorTest extends TestCase
{
    public function testValidApiKeyAllowsRequest(): void
    {
        $authenticator = new ApiKeyAuthenticator('key1,key2');

        $event = $this->createEvent('/api/v1/notifications', 'key1');
        $authenticator->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testMissingApiKeyReturns401Response(): void
    {
        $authenticator = new ApiKeyAuthenticator('key1');

        $event = $this->createEvent('/api/v1/notifications');
        $authenticator->onKernelRequest($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(401, $event->getResponse()->getStatusCode());
    }

    public function testInvalidApiKeyReturns401Response(): void
    {
        $authenticator = new ApiKeyAuthenticator('key1');

        $event = $this->createEvent('/api/v1/notifications', 'wrong-key');
        $authenticator->onKernelRequest($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(401, $event->getResponse()->getStatusCode());
    }

    public function testHealthEndpointSkipsAuthentication(): void
    {
        $authenticator = new ApiKeyAuthenticator('key1');

        $event = $this->createEvent('/health');
        $authenticator->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testEmptyApiKeysConfigAllowsAllRequests(): void
    {
        $authenticator = new ApiKeyAuthenticator('');

        $event = $this->createEvent('/api/v1/notifications');
        $authenticator->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testMultipleValidKeysAreAccepted(): void
    {
        $authenticator = new ApiKeyAuthenticator('key1,key2,key3');

        $event = $this->createEvent('/api/v1/notifications', 'key2');
        $authenticator->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    private function createEvent(string $path, ?string $apiKey = null): RequestEvent
    {
        $request = Request::create($path);

        if ($apiKey !== null) {
            $request->headers->set('X-API-Key', $apiKey);
        }

        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
