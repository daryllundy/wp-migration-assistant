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

final class IncrementalCommand extends Command
{
    protected static $defaultName = 'incremental';

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
            ->setDescription('Run a repeated migration pass with incremental file sync')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source URL/path')
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, 'Destination URL/path')
            ->addOption('chunk-size', null, InputOption::VALUE_OPTIONAL, 'Chunk size', '100MB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getOption('source');
        $destination = (string) $input->getOption('destination');

        if ($source === '' || $destination === '') {
            $io->error('Provide --source and --destination.');
            return Command::INVALID;
        }

        $plan = $this->planner->plan(
            $this->normalizeLocation($source),
            $this->normalizeLocation($destination),
            'incremental',
            ['chunk_size' => $input->getOption('chunk-size')]
        );

        $migrationId = $this->runner->run($plan, true);
        $record = $this->repository->get($migrationId);

        $io->success(sprintf('Incremental migration %s completed (%s)', $migrationId, $record['status'] ?? 'unknown'));
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
