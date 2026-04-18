<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiKeyAuthenticator implements EventSubscriberInterface
{
    private const OPEN_PATHS = ['/health'];

    /** @var list<string> */
    private readonly array $validKeys;

    public function __construct(string $apiKeys)
    {
        // array_filter preserves keys, so the result isn't a list.
        // array_values reindexes to 0..n-1 to match the @var list<string>.
        $this->validKeys = array_values(array_filter(
            array_map('trim', explode(',', $apiKeys)),
            static fn (string $key): bool => $key !== '',
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        foreach (self::OPEN_PATHS as $openPath) {
            if ($path === $openPath) {
                return;
            }
        }

        if ($this->validKeys === []) {
            return;
        }

        $apiKey = $request->headers->get('X-API-Key');

        if ($apiKey === null || !in_array($apiKey, $this->validKeys, true)) {
            $response = new JsonResponse([
                'type' => 'https://example.com/problems/authentication-error',
                'title' => 'Authentication required',
                'status' => 401,
                'detail' => 'A valid API key must be provided in the X-API-Key header.',
            ], 401);
            $response->headers->set('Content-Type', 'application/problem+json');

            $event->setResponse($response);
        }
    }
}
