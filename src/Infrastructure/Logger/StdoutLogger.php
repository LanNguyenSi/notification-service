<?php

declare(strict_types=1);

namespace App\Infrastructure\Logger;

final class StdoutLogger implements LoggerInterface
{
    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        $payload = [
            'level' => $level,
            'message' => $message,
            'context' => $this->normalizeContext($context),
            'timestamp' => gmdate(DATE_ATOM),
        ];

        file_put_contents('php://stdout', json_encode($payload, JSON_THROW_ON_ERROR) . PHP_EOL, FILE_APPEND);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function normalizeContext(array $context): array
    {
        if (!array_key_exists('notification_id', $context)) {
            $context['notification_id'] = 'unknown';
        }

        return $context;
    }
}
