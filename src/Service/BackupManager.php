<?php

declare(strict_types=1);

namespace WPMigration\Service;

use Symfony\Component\Process\Process;

final class BackupManager
{
    private string $backupPath;
    private DatabaseManager $databaseManager;

    public function __construct(string $backupPath, DatabaseManager $databaseManager)
    {
        $this->backupPath = rtrim($backupPath, '/');
        $this->databaseManager = $databaseManager;
    }

    /** @param array<string, mixed> $source */
    public function create(string $migrationId, array $source, ?array $databaseConfig): string
    {
        $destination = $this->backupPath . '/' . $migrationId;
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        if (!empty($source['path']) && is_dir($source['path'])) {
            $archive = $destination . '/files.tar.gz';
            $process = new Process(['tar', '-czf', $archive, '-C', $source['path'], '.']);
            $process->setTimeout(600);
            $process->mustRun();
        }

        if ($databaseConfig !== null) {
            $dumpPath = $destination . '/database.sql';
            $this->databaseManager->dump($databaseConfig, $dumpPath);
        }

        return $destination;
    }

    /** @param array<string, mixed> $destination */
    public function restore(string $backupPath, array $destination, ?array $databaseConfig): void
    {
        $archive = $backupPath . '/files.tar.gz';
        if (file_exists($archive) && !empty($destination['path'])) {
            $process = new Process(['tar', '-xzf', $archive, '-C', $destination['path']]);
            $process->setTimeout(600);
            $process->mustRun();
        }

        $dumpPath = $backupPath . '/database.sql';
        if (file_exists($dumpPath) && $databaseConfig !== null) {
            $this->databaseManager->import($databaseConfig, $dumpPath);
        }
    }
}
