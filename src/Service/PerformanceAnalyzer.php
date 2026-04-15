<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Support\FilesystemHelper;

final class PerformanceAnalyzer
{
    private MigrationRepository $repository;

    public function __construct(MigrationRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return array<string, mixed> */
    public function analyze(string $migrationId): array
    {
        $record = $this->repository->get($migrationId);
        $duration = null;

        if (!empty($record['started_at']) && !empty($record['completed_at'])) {
            $duration = strtotime((string) $record['completed_at']) - strtotime((string) $record['started_at']);
        }

        $bytesTransferred = (int) ($record['bytes_transferred'] ?? 0);
        $throughput = ($duration !== null && $duration > 0) ? (int) round($bytesTransferred / $duration) : null;
        $databaseSize = isset($record['database_size']) ? (int) $record['database_size'] : null;

        return [
            'migration_id' => $migrationId,
            'status' => $record['status'] ?? 'unknown',
            'duration_seconds' => $duration,
            'files_transferred' => $record['files_transferred'] ?? null,
            'bytes_transferred' => $bytesTransferred ?: null,
            'bytes_transferred_human' => $bytesTransferred > 0 ? FilesystemHelper::formatBytes($bytesTransferred) : null,
            'sync_batches' => $record['sync_batches'] ?? null,
            'sync_mode' => $record['sync_mode'] ?? null,
            'throughput_bytes_per_second' => $throughput,
            'throughput_human_per_second' => $throughput !== null ? FilesystemHelper::formatBytes($throughput) . '/s' : null,
            'database_size' => $databaseSize,
            'database_size_human' => $databaseSize !== null ? FilesystemHelper::formatBytes($databaseSize) : null,
            'backup_size_bytes' => $record['backup_size_bytes'] ?? null,
            'backup_size_human' => !empty($record['backup_size_bytes']) ? FilesystemHelper::formatBytes((int) $record['backup_size_bytes']) : null,
            'media_optimized' => $record['media_optimized'] ?? null,
            'tables_optimized' => $record['tables_optimized'] ?? null,
        ];
    }
}
