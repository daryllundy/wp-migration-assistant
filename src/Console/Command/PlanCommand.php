<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MigrationPlanner;
use WPMigration\Support\JsonFile;
use WPMigration\Support\LocationNormalizer;

final class PlanCommand extends Command
{
    protected static $defaultName = 'plan';

    private MigrationPlanner $planner;

    public function __construct(MigrationPlanner $planner)
    {
        parent::__construct();
        $this->planner = $planner;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a migration plan')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source path or URL')
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, 'Destination path or URL')
            ->addOption('strategy', null, InputOption::VALUE_OPTIONAL, 'Migration strategy', 'standard')
            ->addOption('chunk-size', null, InputOption::VALUE_OPTIONAL, 'Chunk size for incremental migrations')
            ->addOption('with-staging', null, InputOption::VALUE_NONE, 'Include staging environment')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output file', 'migration-plan.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getOption('source');
        $destination = (string) $input->getOption('destination');

        if ($source === '' || $destination === '') {
            $io->error('Both --source and --destination are required.');
            return Command::INVALID;
        }

        $plan = $this->planner->plan(
            LocationNormalizer::normalize($source),
            LocationNormalizer::normalize($destination),
            (string) $input->getOption('strategy'),
            [
                'chunk_size' => $input->getOption('chunk-size'),
                'staging' => $input->getOption('with-staging'),
            ]
        );

        $outputPath = (string) $input->getOption('output');
        JsonFile::write($outputPath, $plan->toArray());

        $io->success(sprintf('Migration plan saved to %s', $outputPath));
        return Command::SUCCESS;
    }
}
