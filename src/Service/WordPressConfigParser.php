<?php

declare(strict_types=1);

namespace WPMigration\Service;

final class WordPressConfigParser
{
    /** @return array<string, string> */
    public function parseDatabaseConfig(string $sitePath): array
    {
        $configPath = rtrim($sitePath, '/') . '/wp-config.php';
        if (!file_exists($configPath)) {
            return [];
        }

        $contents = file_get_contents($configPath);
        if ($contents === false) {
            return [];
        }

        $keys = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'];
        $config = [];
        foreach ($keys as $key) {
            if (preg_match("/define\(\s*'" . $key . "'\s*,\s*'([^']*)'\s*\)/", $contents, $matches)) {
                $config[strtolower(str_replace('DB_', '', $key))] = $matches[1];
            }
        }

        if (isset($config['host'])) {
            $config['host'] = $config['host'] === '' ? 'localhost' : $config['host'];
        }

        return $config;
    }
}
