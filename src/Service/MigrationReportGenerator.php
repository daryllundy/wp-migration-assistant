<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Domain\MigrationReport;

final class MigrationReportGenerator
{
    private MigrationRepository $repository;
    private MigrationLogger $logger;
    private PerformanceAnalyzer $performanceAnalyzer;

    public function __construct(MigrationRepository $repository, MigrationLogger $logger, PerformanceAnalyzer $performanceAnalyzer)
    {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->performanceAnalyzer = $performanceAnalyzer;
    }

    public function generate(string $migrationId): MigrationReport
    {
        $record = $this->repository->get($migrationId);
        $logs = $this->logger->read($migrationId);
        $performance = $this->performanceAnalyzer->analyze($migrationId);

        $report = [
            'migration' => $record,
            'performance' => $performance,
            'log_entries' => $logs,
            'summary' => [
                'status' => $record['status'] ?? 'unknown',
                'progress' => $record['progress'] ?? 0,
                'errors' => $record['errors'] ?? [],
                'files_transferred' => $performance['files_transferred'] ?? null,
                'bytes_transferred_human' => $performance['bytes_transferred_human'] ?? null,
                'duration_seconds' => $performance['duration_seconds'] ?? null,
            ],
        ];

        return new MigrationReport($report);
    }
}
