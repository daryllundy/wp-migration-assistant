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

        $sections = $this->requestedSections($input);
        $analysis = $this->siteAnalyzer->analyze($source, [
            'include_plugins' => $sections['plugins'],
            'include_database' => $sections['database'],
            'include_performance' => $sections['performance'],
        ]);
        $compatibility = [];
        $providerSlug = (string) $input->getOption('provider');

        if ($sections['compatibility'] && $providerSlug !== '') {
            $provider = $this->providerRegistry->get($providerSlug);
            $compatibility = $provider->validateCompatibilityFromAnalysis($analysis)['compatibility'] ?? [];
        } elseif ($sections['compatibility']) {
            foreach ($this->providerRegistry->all() as $slug => $provider) {
                $compatibility[$slug] = $provider->validateCompatibilityFromAnalysis($analysis)['compatibility'][$slug] ?? [];
            }
        }

        $payload = ['site_analysis' => $analysis];
        if ($compatibility !== []) {
            $payload['compatibility'] = $compatibility;
        }

        if ($input->getOption('format') === 'json') {
            $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        $io->section('Site Analysis');
        $io->definitionList(
            ['source_type' => (string) ($analysis['source_type'] ?? 'n/a')],
            ['path_or_url' => (string) ($analysis['path'] ?? $analysis['url'] ?? 'n/a')],
            ['wordpress_version' => (string) ($analysis['wordpress_version'] ?? 'n/a')],
            ['php_version' => (string) ($analysis['php_version'] ?? 'n/a')],
            ['mysql_version' => (string) ($analysis['mysql_version'] ?? 'n/a')],
            ['total_size' => (string) ($analysis['total_size'] ?? 'n/a')],
            ['media_files' => (string) ($analysis['media_files'] ?? 'n/a')],
            ['wp_config_found' => array_key_exists('wp_config_found', $analysis) ? json_encode($analysis['wp_config_found']) : 'n/a']
        );

        if (!empty($analysis['plugins'])) {
            $io->section('Plugins');
            $rows = array_map(
                static fn (array $plugin) => [
                    $plugin['slug'] ?? 'unknown',
                    $plugin['name'] ?? 'unknown',
                    $plugin['version'] ?? 'unknown',
                ],
                $analysis['plugins']
            );
            $io->table(['Slug', 'Name', 'Version'], $rows);
        }

        if (isset($analysis['database'])) {
            $io->section('Database');
            $io->definitionList(...$this->formatDefinitionList($analysis['database']));
        }

        if (isset($analysis['performance'])) {
            $io->section('Performance');
            $io->definitionList(...$this->formatDefinitionList($analysis['performance']));
        }

        if ($compatibility !== []) {
            $io->section('Compatibility');
            foreach ($compatibility as $provider => $details) {
                $io->text(sprintf('%s: %s', $provider, ($details['compatible'] ?? false) ? 'compatible' : 'warnings'));
                foreach ($details['warnings'] ?? [] as $warning) {
                    $io->text(' - ' . $warning);
                }
            }
        }

        return Command::SUCCESS;
    }

    /** @return array{plugins: bool, database: bool, performance: bool, compatibility: bool} */
    private function requestedSections(InputInterface $input): array
    {
        $comprehensive = (bool) $input->getOption('comprehensive');
        $plugins = $comprehensive || (bool) $input->getOption('plugins');
        $database = $comprehensive || (bool) $input->getOption('database');
        $performance = $comprehensive || (bool) $input->getOption('performance');
        $hasSpecificSelection = $plugins || $database || $performance;

        if (!$hasSpecificSelection) {
            return [
                'plugins' => true,
                'database' => false,
                'performance' => false,
                'compatibility' => true,
            ];
        }

        return [
            'plugins' => $plugins,
            'database' => $database,
            'performance' => $performance,
            'compatibility' => $plugins,
        ];
    }

    /** @param array<string, mixed> $payload */
    /** @return array<int, array<string, string>> */
    private function formatDefinitionList(array $payload): array
    {
        return array_map(
            static fn (string $key) => [$key => is_scalar($payload[$key] ?? null) || $payload[$key] === null
                ? (string) ($payload[$key] ?? 'n/a')
                : json_encode($payload[$key], JSON_UNESCAPED_SLASHES)],
            array_keys($payload)
        );
    }
}
