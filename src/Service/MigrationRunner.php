<?php

declare(strict_types=1);

namespace WPMigration\Service;

use DateTimeImmutable;
use WPMigration\Domain\MigrationPlan;

final class MigrationRunner
{
    private MigrationRepository $repository;
    private MigrationLogger $logger;
    private FileSyncService $fileSync;
    private DatabaseManager $databaseManager;
    private BackupManager $backupManager;
    private DnsManager $dnsManager;
    private SslManager $sslManager;
    private MediaOptimizer $mediaOptimizer;
    private WordPressConfigParser $configParser;
    private WebhookNotifier $webhookNotifier;

    public function __construct(
        MigrationRepository $repository,
        MigrationLogger $logger,
        FileSyncService $fileSync,
        DatabaseManager $databaseManager,
        BackupManager $backupManager,
        DnsManager $dnsManager,
        SslManager $sslManager,
        MediaOptimizer $mediaOptimizer,
        WordPressConfigParser $configParser,
        WebhookNotifier $webhookNotifier
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->fileSync = $fileSync;
        $this->databaseManager = $databaseManager;
        $this->backupManager = $backupManager;
        $this->dnsManager = $dnsManager;
        $this->sslManager = $sslManager;
        $this->mediaOptimizer = $mediaOptimizer;
        $this->configParser = $configParser;
        $this->webhookNotifier = $webhookNotifier;
    }

    public function run(MigrationPlan $plan, bool $incremental = false): string
    {
        $record = $plan->toArray();
        $record['status'] = 'in_progress';
        $record['progress'] = 0;
        $record['started_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);
        $record['errors'] = [];
        $record['warnings'] = [];

        $migrationId = $this->repository->create($record);
        $this->logger->info($migrationId, 'Migration started');
        $this->webhookNotifier->notify('migration.started', ['migration_id' => $migrationId]);

        try {
            $this->updateProgress($migrationId, 10, 'backup');
            $source = $plan->source();
            $destination = $plan->destination();
            $sourceDb = $this->resolveDatabaseConfig($source);
            $destinationDb = $this->resolveDatabaseConfig($destination);
            $backupPath = $this->backupManager->create($migrationId, $source, $sourceDb);
            $this->repository->update($migrationId, ['backup_path' => $backupPath]);

            if (!empty($source['path']) && !empty($destination['path'])) {
                $this->updateProgress($migrationId, 30, 'files');
                $this->fileSync->sync($source['path'], $destination['path']);
            }

            if ($sourceDb !== null && $destinationDb !== null) {
                $this->updateProgress($migrationId, 50, 'database');
                $dumpPath = $this->tempDumpPath($migrationId);
                $this->databaseManager->dump($sourceDb, $dumpPath);
                $this->databaseManager->import($destinationDb, $dumpPath);
            }

            if ($incremental && !empty($source['path']) && !empty($destination['path'])) {
                $this->updateProgress($migrationId, 65, 'incremental_sync');
                $this->fileSync->sync($source['path'], $destination['path'], true);
            }

            $this->updateProgress($migrationId, 75, 'optimizations');
            $options = $plan->options();
            if (($options['optimization'] ?? false) || ($options['database_optimization'] ?? false) || $this->optionEnabled($plan, 'database_optimization')) {
                if ($destinationDb !== null) {
                    $optimizationResult = $this->databaseManager->optimize($destinationDb);
                    $this->repository->update($migrationId, $optimizationResult);
                }
            }

            if ($this->optionEnabled($plan, 'cdn_enabled') && !empty($destination['path'])) {
                $mediaResult = $this->mediaOptimizer->optimize($destination['path']);
                $this->repository->update($migrationId, ['media_optimized' => $mediaResult['optimized']]);
            }

            $this->updateProgress($migrationId, 85, 'dns_ssl');
            if (!empty($destination['url'])) {
                $domain = $this->resolveDestinationDomain($destination['url']);
                $this->dnsManager->updateRecord($domain, [
                    'type' => 'A',
                    'value' => $destination['host'] ?? '127.0.0.1',
                ]);

                if ($this->optionEnabled($plan, 'ssl_enabled')) {
                    $this->sslManager->issueCertificate($domain, $this->optionValue($plan, 'ssl_email') ?? 'admin@' . $domain);
                }
            }

            $this->updateProgress($migrationId, 100, 'completed');
            $this->repository->update($migrationId, [
                'status' => 'completed',
                'completed_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            ]);
            $this->logger->info($migrationId, 'Migration completed');
            $this->webhookNotifier->notify('migration.completed', ['migration_id' => $migrationId]);
        } catch (\Throwable $exception) {
            $this->repository->update($migrationId, [
                'status' => 'failed',
                'failed_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
                'error_message' => $exception->getMessage(),
            ]);
            $this->logger->error($migrationId, 'Migration failed', [
                'message' => $exception->getMessage(),
            ]);
            $this->webhookNotifier->notify('migration.failed', [
                'migration_id' => $migrationId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $migrationId;
    }

    public function rollback(string $migrationId): void
    {
        $record = $this->repository->get($migrationId);
        $backupPath = $record['backup_path'] ?? null;
        if (!$backupPath) {
            throw new \RuntimeException('No backup found for rollback.');
        }

        $destination = $record['destination'] ?? [];
        $destinationDb = $this->resolveDatabaseConfig($destination);
        $this->backupManager->restore($backupPath, $destination, $destinationDb);

        $this->repository->update($migrationId, [
            'status' => 'rolled_back',
            'completed_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
        ]);
        $this->logger->warning($migrationId, 'Migration rolled back');
        $this->webhookNotifier->notify('migration.rolled_back', ['migration_id' => $migrationId]);
    }

    /** @param array<string, mixed> $source */
    private function resolveDatabaseConfig(array $source): ?array
    {
        if (!empty($source['database'])) {
            return $source['database'];
        }

        if (!empty($source['path'])) {
            $config = $this->configParser->parseDatabaseConfig($source['path']);
            if ($config !== []) {
                return [
                    'host' => $config['host'] ?? 'localhost',
                    'name' => $config['name'] ?? '',
                    'user' => $config['user'] ?? '',
                    'password' => $config['password'] ?? '',
                ];
            }
        }

        return null;
    }

    private function updateProgress(string $migrationId, int $progress, string $step): void
    {
        $this->repository->update($migrationId, [
            'progress' => $progress,
            'current_step' => $step,
        ]);
        $this->logger->info($migrationId, 'Progress update', [
            'progress' => $progress,
            'step' => $step,
        ]);
    }

    private function tempDumpPath(string $migrationId): string
    {
        $path = sys_get_temp_dir() . '/' . $migrationId . '-dump.sql';
        return $path;
    }

    private function resolveDestinationDomain(string $value): string
    {
        $host = parse_url($value, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return $host;
        }

        return $value;
    }

    private function optionEnabled(MigrationPlan $plan, string $key): bool
    {
        return (bool) $this->optionValue($plan, $key);
    }

    private function optionValue(MigrationPlan $plan, string $key): mixed
    {
        $options = $plan->options();
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        $destination = $plan->destination();
        $config = $destination['config']['config'] ?? $destination['config'] ?? [];

        return $config[$key] ?? null;
    }
}
