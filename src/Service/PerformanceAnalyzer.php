<?php

declare(strict_types=1);

namespace WPMigration\Service;

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

        return [
            'migration_id' => $migrationId,
            'duration_seconds' => $duration,
            'files_transferred' => $record['files_transferred'] ?? null,
            'database_size' => $record['database_size'] ?? null,
            'media_optimized' => $record['media_optimized'] ?? null,
        ];
    }
}
