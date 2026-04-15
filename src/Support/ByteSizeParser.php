<?php

declare(strict_types=1);

namespace WPMigration\Support;

final class ByteSizeParser
{
    public static function parse(string|int|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $normalized = strtoupper(trim($value));
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(B|KB|MB|GB|TB)?$/', $normalized, $matches) !== 1) {
            return null;
        }

        $number = (float) $matches[1];
        $unit = $matches[2] ?? 'B';
        $multiplier = match ($unit) {
            'TB' => 1024 ** 4,
            'GB' => 1024 ** 3,
            'MB' => 1024 ** 2,
            'KB' => 1024,
            default => 1,
        };

        return (int) round($number * $multiplier);
    }
}
