<?php

declare(strict_types=1);

namespace WPMigration\Service;

use DateTimeImmutable;

final class MigrationLogger
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, '/');
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /** @param array<string, mixed> $context */
    public function info(string $migrationId, string $message, array $context = []): void
    {
        $this->write($migrationId, 'INFO', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $migrationId, string $message, array $context = []): void
    {
        $this->write($migrationId, 'WARN', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $migrationId, string $message, array $context = []): void
    {
        $this->write($migrationId, 'ERROR', $message, $context);
    }

    /** @return array<int, string> */
    public function read(string $migrationId): array
    {
        $path = $this->logFile($migrationId);
        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        return array_values(array_filter($lines, static fn (string $line) => $line !== ''));
    }

    /** @param array<string, mixed> $context */
    private function write(string $migrationId, string $level, string $message, array $context): void
    {
        $timestamp = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);
        $payload = $context !== [] ? json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $line = sprintf('[%s] %s %s %s', $timestamp, $level, $message, $payload);
        file_put_contents($this->logFile($migrationId), trim($line) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function logFile(string $migrationId): string
    {
        return $this->logPath . '/' . $migrationId . '.log';
    }
}
