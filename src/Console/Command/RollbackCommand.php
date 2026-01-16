<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MigrationRunner;

final class RollbackCommand extends Command
{
    protected static $defaultName = 'rollback';

    private MigrationRunner $runner;

    public function __construct(MigrationRunner $runner)
    {
        parent::__construct();
        $this->runner = $runner;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Rollback a migration')
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

        $this->runner->rollback($migrationId);
        $io->success(sprintf('Rollback completed for %s', $migrationId));
        return Command::SUCCESS;
    }
}
