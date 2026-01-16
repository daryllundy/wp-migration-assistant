<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\ProviderRegistry;
use WPMigration\Service\SiteAnalyzer;

final class AnalyzeCommand extends Command
{
    protected static $defaultName = 'analyze';

    private SiteAnalyzer $siteAnalyzer;
    private ProviderRegistry $providerRegistry;

    public function __construct(SiteAnalyzer $siteAnalyzer, ProviderRegistry $providerRegistry)
    {
        parent::__construct();
        $this->siteAnalyzer = $siteAnalyzer;
        $this->providerRegistry = $providerRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Analyze a WordPress site for migration readiness')
            ->addOption('source-url', null, InputOption::VALUE_REQUIRED, 'Source URL to analyze')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Local path to WordPress site')
            ->addOption('provider', null, InputOption::VALUE_OPTIONAL, 'Hosting provider to evaluate')
            ->addOption('comprehensive', null, InputOption::VALUE_NONE, 'Run comprehensive analysis')
            ->addOption('plugins', null, InputOption::VALUE_NONE, 'Include plugin compatibility checks')
            ->addOption('database', null, InputOption::VALUE_NONE, 'Include database analysis')
            ->addOption('performance', null, InputOption::VALUE_NONE, 'Include performance analysis')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Output format', 'table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) ($input->getOption('source-url') ?? $input->getOption('source'));

        if ($source === '') {
            $io->error('Provide --source-url or --source.');
            return Command::INVALID;
        }

        $analysis = $this->siteAnalyzer->analyze($source);
        $compatibility = [];
        $providerSlug = (string) $input->getOption('provider');

        if ($providerSlug !== '') {
            $provider = $this->providerRegistry->get($providerSlug);
            $compatibility = $provider->validateCompatibility($source)['compatibility'] ?? [];
        } else {
            foreach ($this->providerRegistry->all() as $slug => $provider) {
                $compatibility[$slug] = $provider->validateCompatibility($source)['compatibility'][$slug] ?? [];
            }
        }

        $payload = [
            'site_analysis' => $analysis,
            'compatibility' => $compatibility,
        ];

        if ($input->getOption('format') === 'json') {
            $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        $io->section('Site Analysis');
        $io->definitionList(
            ...array_map(
                static fn (string $key) => [$key => (string) ($analysis[$key] ?? 'n/a')],
                array_keys($analysis)
            )
        );

        $io->section('Compatibility');
        foreach ($compatibility as $provider => $details) {
            $io->text(sprintf('%s: %s', $provider, ($details['compatible'] ?? false) ? 'compatible' : 'warnings'));
            foreach ($details['warnings'] ?? [] as $warning) {
                $io->text(' - ' . $warning);
            }
        }

        return Command::SUCCESS;
    }
}
