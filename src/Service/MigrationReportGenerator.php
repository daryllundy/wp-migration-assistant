<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Domain\MigrationReport;

final class MigrationReportGenerator
{
    private MigrationRepository $repository;
    private MigrationLogger $logger;

    public function __construct(MigrationRepository $repository, MigrationLogger $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function generate(string $migrationId): MigrationReport
    {
        $record = $this->repository->get($migrationId);
        $logs = $this->logger->read($migrationId);

        $report = [
            'migration' => $record,
            'log_entries' => $logs,
            'summary' => [
                'status' => $record['status'] ?? 'unknown',
                'progress' => $record['progress'] ?? 0,
                'errors' => $record['errors'] ?? [],
            ],
        ];

        return new MigrationReport($report);
    }
}
