<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use WPMigration\Support\FilesystemHelper;

final class FilesystemHelperTest extends TestCase
{
    public function testDirectoryMetricsAggregatesTotalAndMatchedSizesInOnePass(): void
    {
        $root = sys_get_temp_dir() . '/wp-migration-metrics-' . bin2hex(random_bytes(4));
        $uploads = $root . '/wp-content/uploads';
        mkdir($uploads, 0755, true);
        mkdir($root . '/wp-content/themes', 0755, true);

        file_put_contents($uploads . '/image.jpg', '12345');
        file_put_contents($root . '/wp-content/themes/index.php', '123');

        try {
            $metrics = FilesystemHelper::directoryMetrics($root, $uploads);

            $this->assertSame(8, $metrics['total_size']);
            $this->assertSame(5, $metrics['matched_size']);
        } finally {
            unlink($uploads . '/image.jpg');
            unlink($root . '/wp-content/themes/index.php');
            rmdir($root . '/wp-content/themes');
            rmdir($uploads);
            rmdir($root . '/wp-content');
            rmdir($root);
        }
    }
}
