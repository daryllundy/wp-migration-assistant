<?php

declare(strict_types=1);

namespace WPMigration\Support;

final class LocationNormalizer
{
    /** @return array<string, mixed> */
    public static function normalize(string $value): array
    {
        if (is_dir($value)) {
            return [
                'path' => $value,
            ];
        }

        return [
            'url' => $value,
        ];
    }
}
