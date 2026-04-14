<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use WPMigration\Service\MigrationRepository;
use WPMigration\Service\PerformanceAnalyzer;

final class PerformanceAnalyzerTest extends TestCase
{
    public function testAnalyzeReturnsDerivedTransferMetrics(): void
    {
        $storage = sys_get_temp_dir() . '/wp-migration-performance-' . bin2hex(random_bytes(4));
        mkdir($storage, 0755, true);

        $repository = new MigrationRepository($storage);
        $repository->create([
            'migration_id' => 'mig_test',
            'status' => 'completed',
            'started_at' => '2026-01-01T00:00:00+00:00',
            'completed_at' => '2026-01-01T00:00:10+00:00',
            'files_transferred' => 12,
            'bytes_transferred' => 2048,
            'sync_batches' => 2,
            'sync_mode' => 'chunked',
            'database_size' => 1024,
            'backup_size_bytes' => 4096,
        ]);

        $analyzer = new PerformanceAnalyzer($repository);

        try {
            $result = $analyzer->analyze('mig_test');

            $this->assertSame(10, $result['duration_seconds']);
            $this->assertSame(2048, $result['bytes_transferred']);
            $this->assertSame('2.00KB', $result['bytes_transferred_human']);
            $this->assertSame(205, $result['throughput_bytes_per_second']);
            $this->assertSame('chunked', $result['sync_mode']);
            $this->assertSame('4.00KB', $result['backup_size_human']);
        } finally {
            @unlink($storage . '/mig_test.json');
            @rmdir($storage);
        }
    }
}
