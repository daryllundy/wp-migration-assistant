<?php

declare(strict_types=1);

namespace WPMigration\Support;

final class JsonFile
{
    /** @return array<string, mixed> */
    public static function read(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }

        $data = json_decode($contents, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $payload */
    public static function write(string $path, array $payload): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON for ' . $path);
        }

        file_put_contents($path, $json . PHP_EOL, LOCK_EX);
    }
}
