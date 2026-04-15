<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MigrationLogger;
use WPMigration\Service\MigrationRepository;

final class StatusCommand extends Command
{
    protected static $defaultName = 'status';

    private MigrationRepository $repository;
    private MigrationLogger $logger;

    public function __construct(MigrationRepository $repository, MigrationLogger $logger)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Show migration status')
            ->addOption('migration-id', null, InputOption::VALUE_REQUIRED, 'Migration ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $migrationId = (string) $input->getOption('migration-id');

        if ($migrationId === '') {
            $io->error('Provide --migration-id.');
            return Command::INVALID;
        }

        $record = $this->repository->get($migrationId);
        if ($record === []) {
            $io->error('Migration not found.');
            return Command::FAILURE;
        }

        $io->section('Status');
        $io->definitionList(
            ['ID' => $migrationId],
            ['Status' => $record['status'] ?? 'unknown'],
            ['Progress' => ($record['progress'] ?? 0) . '%'],
            ['Current Step' => $record['current_step'] ?? 'n/a'],
            ['Started At' => $record['started_at'] ?? 'n/a'],
            ['Completed At' => $record['completed_at'] ?? ($record['failed_at'] ?? 'n/a')],
            ['Backup Path' => $record['backup_path'] ?? 'n/a'],
            ['Backup Size (bytes)' => (string) ($record['backup_size_bytes'] ?? '0')],
            ['Files Transferred' => (string) ($record['files_transferred'] ?? '0')],
            ['Bytes Transferred' => (string) ($record['bytes_transferred'] ?? '0')],
            ['Sync Batches' => (string) ($record['sync_batches'] ?? '0')],
            ['Sync Mode' => (string) ($record['sync_mode'] ?? 'n/a')],
            ['Tables Optimized' => (string) ($record['tables_optimized'] ?? '0')],
            ['Media Optimized' => (string) ($record['media_optimized'] ?? '0')],
            ['Database Dump Size (bytes)' => (string) ($record['database_size'] ?? '0')]
        );

        if (!empty($record['error_message'])) {
            $io->warning($record['error_message']);
        }

        $io->section('Recent Logs');
        $logs = array_slice($this->logger->read($migrationId), -5);
        foreach ($logs as $line) {
            $io->text($line);
        }

        return Command::SUCCESS;
    }
}
