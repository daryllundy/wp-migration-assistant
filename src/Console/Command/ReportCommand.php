<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use WPMigration\Service\MigrationReportGenerator;
use WPMigration\Support\JsonFile;

final class ReportCommand extends Command
{
    protected static $defaultName = 'report';

    private MigrationReportGenerator $generator;

    public function __construct(MigrationReportGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate migration report')
            ->addOption('migration-id', null, InputOption::VALUE_REQUIRED, 'Migration ID')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Format', 'json')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output file', 'migration-report.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $migrationId = (string) $input->getOption('migration-id');
        if ($migrationId === '') {
            $io->error('Provide --migration-id.');
            return Command::INVALID;
        }

        $report = $this->generator->generate($migrationId);
        $outputPath = (string) $input->getOption('output');

        $format = (string) $input->getOption('format');
        if ($format === 'json') {
            JsonFile::write($outputPath, $report->toArray());
            $io->success(sprintf('Report written to %s', $outputPath));
            return Command::SUCCESS;
        }

        if ($format === 'pdf') {
            $html = $this->renderHtml($report->toArray());
            $htmlPath = sys_get_temp_dir() . '/' . $migrationId . '-report.html';
            file_put_contents($htmlPath, $html);

            $process = new Process(['wkhtmltopdf', $htmlPath, $outputPath]);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                $io->success(sprintf('PDF report written to %s', $outputPath));
                return Command::SUCCESS;
            }

            $io->warning('wkhtmltopdf not available, writing JSON instead.');
            JsonFile::write($outputPath, $report->toArray());
            return Command::SUCCESS;
        }

        $output->writeln(json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function renderHtml(array $report): string
    {
        $summary = $report['summary'] ?? [];
        $performance = $report['performance'] ?? [];
        $logs = $report['log_entries'] ?? [];

        $logHtml = '';
        foreach ($logs as $line) {
            $logHtml .= '<li>' . htmlspecialchars((string) $line, ENT_QUOTES) . '</li>';
        }

        return sprintf(
            '<h1>Migration Report</h1><p>Status: %s</p><p>Progress: %s%%</p><p>Duration: %s seconds</p><p>Files transferred: %s</p><p>Bytes transferred: %s</p><p>Sync mode: %s</p><h2>Recent Logs</h2><ul>%s</ul>',
            htmlspecialchars((string) ($summary['status'] ?? 'unknown'), ENT_QUOTES),
            htmlspecialchars((string) ($summary['progress'] ?? 0), ENT_QUOTES),
            htmlspecialchars((string) ($summary['duration_seconds'] ?? 'n/a'), ENT_QUOTES),
            htmlspecialchars((string) ($summary['files_transferred'] ?? 'n/a'), ENT_QUOTES),
            htmlspecialchars((string) ($summary['bytes_transferred_human'] ?? 'n/a'), ENT_QUOTES),
            htmlspecialchars((string) ($performance['sync_mode'] ?? 'n/a'), ENT_QUOTES),
            $logHtml
        );
    }
}
