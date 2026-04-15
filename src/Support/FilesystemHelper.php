<?php

declare(strict_types=1);

namespace WPMigration\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FilesystemHelper
{
    /** @return array{total_size:int, matched_size:int} */
    public static function directoryMetrics(string $path, ?string $matchedPrefix = null): array
    {
        if (!is_dir($path)) {
            return [
                'total_size' => 0,
                'matched_size' => 0,
            ];
        }

        $normalizedPrefix = $matchedPrefix !== null ? rtrim(str_replace('\\', '/', $matchedPrefix), '/') . '/' : null;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $totalSize = 0;
        $matchedSize = 0;

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $size = $file->getSize();
            $totalSize += $size;

            if ($normalizedPrefix !== null) {
                $pathname = str_replace('\\', '/', $file->getPathname());
                if (str_starts_with($pathname, $normalizedPrefix)) {
                    $matchedSize += $size;
                }
            }
        }

        return [
            'total_size' => $totalSize,
            'matched_size' => $matchedSize,
        ];
    }

    public static function directorySize(string $path): int
    {
        return self::directoryMetrics($path)['total_size'];
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f%s', $value, $units[$unitIndex]);
    }
}
