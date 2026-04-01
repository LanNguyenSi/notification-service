<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\Exception\NoAvailableChannelException;
use App\Contract\Exception\UnsupportedChannelException;
use App\Contract\Exception\ValidationException;
use App\Contract\Interface\NotificationRepositoryInterface;
use App\Contract\Interface\NotificationServiceInterface;
use App\Service\Uuid;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class NotificationController
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
        private readonly NotificationRepositoryInterface $repository,
    ) {
    }

    #[Route('/api/v1/notifications', name: 'app_notifications_send', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);

        try {
            $payload = $this->decodePayload($request);
            $result = $this->notificationService->send(new NotificationRequestDTO(
                recipient: (string) ($payload['recipient'] ?? ''),
                channels: is_array($payload['channels'] ?? null) ? array_values($payload['channels']) : [],
                subject: (string) ($payload['subject'] ?? ''),
                body: (string) ($payload['body'] ?? ''),
                metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
            ));

            return $this->json($result->toArray(), JsonResponse::HTTP_ACCEPTED, $requestId);
        } catch (JsonException|ValidationException $exception) {
            return $this->problem('https://example.com/problems/validation-error', 'Validation error', JsonResponse::HTTP_BAD_REQUEST, $exception->getMessage(), $requestId);
        } catch (NoAvailableChannelException|UnsupportedChannelException $exception) {
            return $this->problem('https://example.com/problems/no-channel-available', 'No channels available', JsonResponse::HTTP_SERVICE_UNAVAILABLE, $exception->getMessage(), $requestId);
        } catch (Throwable $exception) {
            return $this->problem('https://example.com/problems/internal-server-error', 'Internal server error', JsonResponse::HTTP_INTERNAL_SERVER_ERROR, 'The notification could not be processed.', $requestId);
        }
    }

    /** @return array<string, mixed> */
    private function decodePayload(Request $request): array
    {
        $content = trim($request->getContent());
        if ($content === '') {
            throw new JsonException('Request body must not be empty.');
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status, string $requestId): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function problem(string $type, string $title, int $status, string $detail, string $requestId): JsonResponse
    {
        $response = new JsonResponse([
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ], $status);
        $response->headers->set('Content-Type', 'application/problem+json');
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    #[Route('/api/v1/notifications/{id}', name: 'app_notifications_show', methods: ['GET'])]
    public function show(Request $request, string $id): JsonResponse
    {
        $requestId = $this->requestId($request);

        try {
            $notification = $this->repository->findById($id);

            if ($notification === null) {
                return $this->problem(
                    'https://example.com/problems/not-found',
                    'Not found',
                    JsonResponse::HTTP_NOT_FOUND,
                    sprintf('Notification "%s" not found.', $id),
                    $requestId,
                );
            }

            return $this->json([
                'id' => $notification['id'],
                'status' => $notification['status'],
                'recipient' => $notification['recipient'],
                'subject' => $notification['subject'],
                'channels' => $notification['channels'],
                'deliveries' => $notification['deliveries'],
                'created_at' => $notification['created_at'],
                'updated_at' => $notification['updated_at'],
            ], JsonResponse::HTTP_OK, $requestId);
        } catch (Throwable) {
            return $this->problem(
                'https://example.com/problems/internal-server-error',
                'Internal server error',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'The notification could not be retrieved.',
                $requestId,
            );
        }
    }

    private function requestId(Request $request): string
    {
        return $request->headers->get('X-Request-ID', Uuid::v4());
    }
}
