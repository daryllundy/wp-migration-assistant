<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Domain\MigrationPlan;
use WPMigration\Service\MigrationPlanner;
use WPMigration\Service\MigrationRepository;
use WPMigration\Service\MigrationRunner;
use WPMigration\Support\JsonFile;
use WPMigration\Support\LocationNormalizer;

final class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

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
            ->setDescription('Execute a migration')
            ->addOption('plan', null, InputOption::VALUE_OPTIONAL, 'Path to migration plan')
            ->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Source URL/path')
            ->addOption('destination', null, InputOption::VALUE_OPTIONAL, 'Destination URL/path')
            ->addOption('strategy', null, InputOption::VALUE_OPTIONAL, 'Strategy', 'standard');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $planPath = (string) $input->getOption('plan');

        if ($planPath !== '') {
            $payload = JsonFile::read($planPath);
            $plan = new MigrationPlan($payload);
        } else {
            $source = (string) $input->getOption('source');
            $destination = (string) $input->getOption('destination');
            if ($source === '' || $destination === '') {
                $io->error('Provide --plan or both --source and --destination.');
                return Command::INVALID;
            }

            $plan = $this->planner->plan(
                LocationNormalizer::normalize($source),
                LocationNormalizer::normalize($destination),
                (string) $input->getOption('strategy')
            );
        }

        try {
            $migrationId = $this->runner->run($plan, $plan->strategy() === 'incremental');
        } catch (\Throwable $exception) {
            $io->error('Migration failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $record = $this->repository->get($migrationId);

        $io->success(sprintf('Migration %s completed with status %s', $migrationId, $record['status'] ?? 'unknown'));
        return Command::SUCCESS;
    }
}
