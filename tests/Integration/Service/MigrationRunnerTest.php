<?php

declare(strict_types=1);

namespace WPMigration\Tests\Integration\Service;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use WPMigration\Service\BackupManager;
use WPMigration\Service\DatabaseManager;
use WPMigration\Service\DnsManager;
use WPMigration\Service\FileSyncService;
use WPMigration\Service\MediaOptimizer;
use WPMigration\Service\MigrationLogger;
use WPMigration\Service\MigrationPlanner;
use WPMigration\Service\MigrationRepository;
use WPMigration\Service\MigrationRunner;
use WPMigration\Service\SslManager;
use WPMigration\Service\WebhookNotifier;
use WPMigration\Service\WordPressConfigParser;

final class MigrationRunnerTest extends TestCase
{
    public function testRunCompletesLocalFilesystemMigration(): void
    {
        $root = sys_get_temp_dir() . '/wp-migration-runner-' . bin2hex(random_bytes(4));
        $source = $root . '/source-site';
        $destination = $root . '/destination-site';
        $artifactRoot = $root . '/var';

        mkdir($source . '/wp-content/uploads', 0755, true);
        mkdir($source . '/wp-content/plugins/example-plugin', 0755, true);
        mkdir($source . '/wp-includes', 0755, true);
        mkdir($destination, 0755, true);

        file_put_contents($source . '/index.php', "<?php echo 'source';\n");
        file_put_contents($source . '/wp-content/uploads/image.jpg', 'image-bytes');
        file_put_contents(
            $source . '/wp-content/plugins/example-plugin/example-plugin.php',
            "<?php\n/*\nPlugin Name: Example Plugin\nVersion: 1.0.0\n*/\n"
        );
        file_put_contents($source . '/wp-includes/version.php', "<?php\n\$wp_version = '6.8';\n");

        $repository = new MigrationRepository($artifactRoot . '/migrations');
        $logger = new MigrationLogger($artifactRoot . '/logs');
        $runner = new MigrationRunner(
            $repository,
            $logger,
            new FileSyncService(),
            new DatabaseManager(),
            new BackupManager($artifactRoot . '/backups', new DatabaseManager()),
            new DnsManager($artifactRoot . '/dns-records.json'),
            new SslManager($artifactRoot . '/certs'),
            new MediaOptimizer(),
            new WordPressConfigParser(),
            new WebhookNotifier($this->createMock(ClientInterface::class), $artifactRoot . '/webhooks.json')
        );

        $plan = (new MigrationPlanner())->plan(
            ['path' => $source],
            ['path' => $destination],
            'standard'
        );

        try {
            $migrationId = $runner->run($plan);
            $record = $repository->get($migrationId);

            $this->assertSame('completed', $record['status']);
            $this->assertFileExists($destination . '/index.php');
            $this->assertFileExists($destination . '/wp-content/uploads/image.jpg');
            $this->assertFileExists($record['backup_path'] . '/files.tar.gz');
            $this->assertGreaterThanOrEqual(1, (int) ($record['files_transferred'] ?? 0));
            $this->assertNotEmpty($logger->read($migrationId));
        } finally {
            $this->removeDirectory($root);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($path);
    }
}
