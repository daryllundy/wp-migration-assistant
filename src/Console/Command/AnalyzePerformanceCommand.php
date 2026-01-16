<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\PerformanceAnalyzer;

final class AnalyzePerformanceCommand extends Command
{
    protected static $defaultName = 'analyze-performance';

    private PerformanceAnalyzer $analyzer;

    public function __construct(PerformanceAnalyzer $analyzer)
    {
        parent::__construct();
        $this->analyzer = $analyzer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Analyze migration performance')
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

        $result = $this->analyzer->analyze($migrationId);
        $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
