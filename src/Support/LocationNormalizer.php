<?php

declare(strict_types=1);

namespace WPMigration\Support;

final class LocationNormalizer
{
    /** @return array<string, mixed> */
    public static function normalize(string $value): array
    {
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return [
                'url' => $value,
            ];
        }

        if (is_dir($value) || self::looksLikePath($value)) {
            return [
                'path' => $value,
            ];
        }

        return [
            'url' => $value,
        ];
    }

    private static function looksLikePath(string $value): bool
    {
        return str_starts_with($value, '/')
            || str_starts_with($value, './')
            || str_starts_with($value, '../')
            || str_contains($value, DIRECTORY_SEPARATOR);
    }
}
