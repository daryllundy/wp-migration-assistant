<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use WPMigration\Support\LocationNormalizer;

final class LocationNormalizerTest extends TestCase
{
    public function testNormalizeReturnsPathForLocalDirectory(): void
    {
        $path = sys_get_temp_dir() . '/wp-migration-location-' . bin2hex(random_bytes(4));
        mkdir($path, 0755, true);

        try {
            $this->assertSame(['path' => $path], LocationNormalizer::normalize($path));
        } finally {
            rmdir($path);
        }
    }

    public function testNormalizeReturnsUrlForRemoteLocation(): void
    {
        $url = 'https://example.com';

        $this->assertSame(['url' => $url], LocationNormalizer::normalize($url));
    }
}
