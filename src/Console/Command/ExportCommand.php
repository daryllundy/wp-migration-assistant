<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MigrationRepository;
use WPMigration\Support\JsonFile;

final class ExportCommand extends Command
{
    protected static $defaultName = 'export';

    private MigrationRepository $repository;

    public function __construct(MigrationRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Export migration data')
            ->addOption('migration-id', null, InputOption::VALUE_REQUIRED, 'Migration ID')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Format', 'json')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output file', 'migration-export.json');
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
        $outputPath = (string) $input->getOption('output');

        if ($input->getOption('format') === 'json') {
            JsonFile::write($outputPath, $record);
            $io->success(sprintf('Exported data to %s', $outputPath));
            return Command::SUCCESS;
        }

        $output->writeln(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return Command::SUCCESS;
    }
}
