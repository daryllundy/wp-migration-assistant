<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use WPMigration\Service\FileSyncService;

final class FileSyncServiceTest extends TestCase
{
    public function testSyncInChunksCopiesFilesAndDeletesRemovedOnes(): void
    {
        $source = sys_get_temp_dir() . '/wp-migration-sync-source-' . bin2hex(random_bytes(4));
        $destination = sys_get_temp_dir() . '/wp-migration-sync-dest-' . bin2hex(random_bytes(4));
        mkdir($source . '/nested', 0755, true);
        mkdir($destination . '/nested', 0755, true);

        file_put_contents($source . '/a.txt', str_repeat('a', 4));
        file_put_contents($source . '/nested/b.txt', str_repeat('b', 4));
        file_put_contents($destination . '/obsolete.txt', 'old');

        $service = new FileSyncService();

        try {
            $stats = $service->syncInChunks($source, $destination, 4, true);

            $this->assertSame(2, $stats['files_transferred']);
            $this->assertSame(2, $stats['batches']);
            $this->assertFileExists($destination . '/a.txt');
            $this->assertFileExists($destination . '/nested/b.txt');
            $this->assertFileDoesNotExist($destination . '/obsolete.txt');
        } finally {
            @unlink($source . '/a.txt');
            @unlink($source . '/nested/b.txt');
            @unlink($destination . '/a.txt');
            @unlink($destination . '/nested/b.txt');
            @unlink($destination . '/obsolete.txt');
            @rmdir($source . '/nested');
            @rmdir($destination . '/nested');
            @rmdir($source);
            @rmdir($destination);
        }
    }
}
