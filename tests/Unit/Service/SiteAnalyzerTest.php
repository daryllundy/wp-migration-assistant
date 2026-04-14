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

        $analyzer = new SiteAnalyzer(
            $this->createMock(ClientInterface::class),
            new PluginCompatibilityAnalyzer()
        );

        try {
            $result = $analyzer->analyze($root);

            $this->assertSame('local', $result['source_type']);
            $this->assertSame('5.00B', $result['media_files']);
            $this->assertNotEmpty($result['plugins']);
            $this->assertSame('sample-plugin', $result['plugins'][0]['slug']);
        } finally {
            unlink($plugins . '/sample-plugin.php');
            unlink($uploads . '/asset.jpg');
            unlink($root . '/wp-includes/version.php');
            rmdir($plugins);
            rmdir($root . '/wp-content/plugins');
            rmdir($uploads);
            rmdir($root . '/wp-content');
            rmdir($root . '/wp-includes');
            rmdir($root);
        }
    }
}
