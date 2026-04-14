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
use WPMigration\Support\LocationNormalizer;

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
            ->setDescription('Run a staged migration workflow before the final destination pass')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source URL/path')
            ->addOption('staging', null, InputOption::VALUE_REQUIRED, 'Staging path or URL')
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

        $stagingLocation = LocationNormalizer::normalize($staging);
        $sourceLocation = LocationNormalizer::normalize($source);
        $destinationLocation = LocationNormalizer::normalize($destination);

        try {
            $stagePlan = $this->planner->plan(
                $sourceLocation,
                $stagingLocation,
                'standard',
                ['staging_phase' => 'warmup']
            );
            $stageMigrationId = $this->runner->run($stagePlan);

            $finalPlan = $this->planner->plan(
                $stagingLocation,
                $destinationLocation,
                'standard',
                ['staging_phase' => 'cutover', 'staged_from_migration' => $stageMigrationId]
            );
            $finalMigrationId = $this->runner->run($finalPlan);
        } catch (\Throwable $exception) {
            $io->error('Staged migration failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $stageRecord = $this->repository->get($stageMigrationId);
        $finalRecord = $this->repository->get($finalMigrationId);

        $io->success(sprintf(
            'Staging pass %s (%s), final pass %s (%s)',
            $stageMigrationId,
            $stageRecord['status'] ?? 'unknown',
            $finalMigrationId,
            $finalRecord['status'] ?? 'unknown'
        ));
        return Command::SUCCESS;
    }
}
