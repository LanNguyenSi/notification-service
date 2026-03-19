<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok']);
        $response->headers->set('X-Request-ID', $request->headers->get('X-Request-ID', Uuid::v4()));

        return $response;
    }
}
