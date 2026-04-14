<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Service;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use WPMigration\Service\PluginCompatibilityAnalyzer;
use WPMigration\Service\SiteAnalyzer;

final class SiteAnalyzerTest extends TestCase
{
    public function testAnalyzeLocalUsesSinglePassMetricsForSiteAndUploads(): void
    {
        $root = $this->createFixtureSite();
        $analyzer = $this->createAnalyzer();

        try {
            $result = $analyzer->analyze($root);

            $this->assertSame('local', $result['source_type']);
            $this->assertSame('5.00B', $result['media_files']);
            $this->assertNotEmpty($result['plugins']);
            $this->assertSame('sample-plugin', $result['plugins'][0]['slug']);
        } finally {
            $this->removeFixtureSite($root);
        }
    }

    public function testAnalyzeLocalCanRequestDatabaseAndPerformanceSectionsWithoutPluginInventory(): void
    {
        $root = $this->createFixtureSite();
        $analyzer = $this->createAnalyzer();

        try {
            $result = $analyzer->analyze($root, [
                'include_plugins' => false,
                'include_database' => true,
                'include_performance' => true,
            ]);

            $this->assertArrayNotHasKey('plugins', $result);
            $this->assertSame('unavailable', $result['database']['status']);
            $this->assertSame(0, $result['performance']['plugin_count']);
            $this->assertSame('standard', $result['performance']['estimated_sync_profile']);
        } finally {
            $this->removeFixtureSite($root);
        }
    }

    private function createAnalyzer(): SiteAnalyzer
    {
        return new SiteAnalyzer(
            $this->createMock(ClientInterface::class),
            new PluginCompatibilityAnalyzer()
        );
    }

    private function createFixtureSite(): string
    {
        $root = sys_get_temp_dir() . '/wp-migration-site-' . bin2hex(random_bytes(4));
        $uploads = $root . '/wp-content/uploads';
        $plugins = $root . '/wp-content/plugins/sample-plugin';
        mkdir($uploads, 0755, true);
        mkdir($plugins, 0755, true);
        mkdir($root . '/wp-includes', 0755, true);

        file_put_contents($root . '/wp-includes/version.php', <<<'PHP'
<?php
$wp_version = '6.8';
PHP
        );
        file_put_contents($uploads . '/asset.jpg', '12345');
        file_put_contents($plugins . '/sample-plugin.php', "<?php\n/*\nPlugin Name: Sample Plugin\nVersion: 1.2.3\n*/\n");

        return $root;
    }

    private function removeFixtureSite(string $root): void
    {
        unlink($root . '/wp-content/plugins/sample-plugin/sample-plugin.php');
        unlink($root . '/wp-content/uploads/asset.jpg');
        unlink($root . '/wp-includes/version.php');
        rmdir($root . '/wp-content/plugins/sample-plugin');
        rmdir($root . '/wp-content/plugins');
        rmdir($root . '/wp-content/uploads');
        rmdir($root . '/wp-content');
        rmdir($root . '/wp-includes');
        rmdir($root);
    }
}
