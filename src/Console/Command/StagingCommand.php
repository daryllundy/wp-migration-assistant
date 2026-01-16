<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MigrationPlanner;
use WPMigration\Service\MigrationRepository;
use WPMigration\Service\MigrationRunner;

final class StagingCommand extends Command
{
    protected static $defaultName = 'staging';

    private MigrationPlanner $planner;
    private MigrationRunner $runner;
    private MigrationRepository $repository;

    public function __construct(MigrationPlanner $planner, MigrationRunner $runner, MigrationRepository $repository)
    {
        parent::__construct();
        $this->planner = $planner;
        $this->runner = $runner;
        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run a migration with staging environment')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source URL/path')
            ->addOption('staging', null, InputOption::VALUE_REQUIRED, 'Staging URL')
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, 'Destination URL/path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getOption('source');
        $destination = (string) $input->getOption('destination');
        $staging = (string) $input->getOption('staging');

        if ($source === '' || $destination === '' || $staging === '') {
            $io->error('Provide --source, --staging, and --destination.');
            return Command::INVALID;
        }

        $plan = $this->planner->plan(
            $this->normalizeLocation($source),
            $this->normalizeLocation($destination),
            'zero-downtime',
            ['staging_url' => $staging]
        );

        $migrationId = $this->runner->run($plan);
        $record = $this->repository->get($migrationId);

        $io->success(sprintf('Staging migration %s completed (%s)', $migrationId, $record['status'] ?? 'unknown'));
        return Command::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function normalizeLocation(string $value): array
    {
        if (is_dir($value)) {
            return [
                'path' => $value,
                'url' => $value,
            ];
        }

        return [
            'url' => $value,
        ];
    }
}
