<?php

declare(strict_types=1);

namespace WPMigration\Console;

use GuzzleHttp\Client;
use Symfony\Component\Console\Application;
use WPMigration\Console\Command\AnalyzeCommand;
use WPMigration\Console\Command\AnalyzePerformanceCommand;
use WPMigration\Console\Command\CompressImagesCommand;
use WPMigration\Console\Command\CreateCommand;
use WPMigration\Console\Command\ExportCommand;
use WPMigration\Console\Command\IncrementalCommand;
use WPMigration\Console\Command\MalwareScanCommand;
use WPMigration\Console\Command\MigrateCommand;
use WPMigration\Console\Command\OptimizeMediaCommand;
use WPMigration\Console\Command\PlanCommand;
use WPMigration\Console\Command\ProvidersCommand;
use WPMigration\Console\Command\ReportCommand;
use WPMigration\Console\Command\RollbackCommand;
use WPMigration\Console\Command\SecurityScanCommand;
use WPMigration\Console\Command\SetupCdnCommand;
use WPMigration\Console\Command\StagingCommand;
use WPMigration\Console\Command\StatusCommand;
use WPMigration\Console\Command\TestWebhookCommand;
use WPMigration\Console\Command\VulnerabilityScanCommand;
use WPMigration\Console\Command\WebhookCommand;
use WPMigration\Service\BackupManager;
use WPMigration\Service\CdnManager;
use WPMigration\Service\DatabaseManager;
use WPMigration\Service\DnsManager;
use WPMigration\Service\FileSyncService;
use WPMigration\Service\MediaOptimizer;
use WPMigration\Service\MigrationPlanner;
use WPMigration\Service\MigrationReportGenerator;
use WPMigration\Service\MigrationRepository;
use WPMigration\Service\MigrationRunner;
use WPMigration\Service\MigrationLogger;
use WPMigration\Service\PerformanceAnalyzer;
use WPMigration\Service\PluginCompatibilityAnalyzer;
use WPMigration\Service\ProviderRegistry;
use WPMigration\Service\SecurityScanner;
use WPMigration\Service\SiteAnalyzer;
use WPMigration\Service\SslManager;
use WPMigration\Service\WebhookNotifier;
use WPMigration\Service\WordPressConfigParser;
use WPMigration\Support\AppConfig;

final class ApplicationFactory
{
    public static function create(string $rootPath): Application
    {
        $config = new AppConfig($rootPath);
        $httpClient = new Client(['timeout' => 10]);
        $pluginAnalyzer = new PluginCompatibilityAnalyzer();
        $siteAnalyzer = new SiteAnalyzer($httpClient, $pluginAnalyzer);
        $providerRegistry = new ProviderRegistry($siteAnalyzer, $pluginAnalyzer);

        $repository = new MigrationRepository($config->path('var/migrations'));
        $logger = new MigrationLogger($config->path('var/logs'));
        $planner = new MigrationPlanner();
        $databaseManager = new DatabaseManager();
        $fileSync = new FileSyncService();
        $backupManager = new BackupManager($config->path('var/backups'), $databaseManager);
        $dnsManager = new DnsManager($config->path('var/dns-records.json'));
        $sslManager = new SslManager($config->path('var/certs'));
        $mediaOptimizer = new MediaOptimizer();
        $configParser = new WordPressConfigParser();
        $webhookNotifier = new WebhookNotifier($httpClient, $config->path('var/webhooks.json'));
        $runner = new MigrationRunner(
            $repository,
            $logger,
            $fileSync,
            $databaseManager,
            $backupManager,
            $dnsManager,
            $sslManager,
            $mediaOptimizer,
            $configParser,
            $webhookNotifier
        );
        $reportGenerator = new MigrationReportGenerator($repository, $logger);
        $performanceAnalyzer = new PerformanceAnalyzer($repository);
        $cdnManager = new CdnManager($config->path('var/cdn.json'));
        $securityScanner = new SecurityScanner();

        $application = new Application('WP Migration Assistant', '1.0.0');
        $application->add(new AnalyzeCommand($siteAnalyzer, $providerRegistry));
        $application->add(new PlanCommand($planner));
        $application->add(new MigrateCommand($planner, $runner, $repository));
        $application->add(new StatusCommand($repository, $logger));
        $application->add(new ProvidersCommand($providerRegistry));
        $application->add(new CreateCommand($providerRegistry, $planner));
        $application->add(new IncrementalCommand($planner, $runner, $repository));
        $application->add(new StagingCommand($planner, $runner, $repository));
        $application->add(new RollbackCommand($runner));
        $application->add(new SecurityScanCommand($securityScanner));
        $application->add(new MalwareScanCommand($securityScanner));
        $application->add(new VulnerabilityScanCommand($securityScanner));
        $application->add(new AnalyzePerformanceCommand($performanceAnalyzer));
        $application->add(new ReportCommand($reportGenerator));
        $application->add(new ExportCommand($repository));
        $application->add(new OptimizeMediaCommand($mediaOptimizer));
        $application->add(new CompressImagesCommand($mediaOptimizer));
        $application->add(new SetupCdnCommand($cdnManager));
        $application->add(new WebhookCommand($webhookNotifier));
        $application->add(new TestWebhookCommand($webhookNotifier));

        return $application;
    }
}
