<?php

declare(strict_types=1);

namespace WPMigration\Service;

use DirectoryIterator;

final class PluginCompatibilityAnalyzer
{
    /** @var array<string, array<string, string>> */
    private array $knownIssues = [
        'kinsta' => [
            'wp-super-cache' => 'Caching conflicts with Kinsta cache.',
        ],
        'pressable' => [
            'w3-total-cache' => 'Conflicts with managed object cache.',
        ],
        'godaddy' => [
            'autoptimize' => 'May overlap with GoDaddy optimizer.',
        ],
        'inmotion' => [
            'litespeed-cache' => 'Requires special server config.',
        ],
    ];

    /** @return array<int, array<string, string>> */
    public function listPlugins(string $sitePath): array
    {
        $pluginsPath = rtrim($sitePath, '/') . '/wp-content/plugins';
        if (!is_dir($pluginsPath)) {
            return [];
        }

        $plugins = [];
        foreach (new DirectoryIterator($pluginsPath) as $pluginDir) {
            if ($pluginDir->isDot() || !$pluginDir->isDir()) {
                continue;
            }

            $mainFile = $this->locatePluginFile($pluginDir->getPathname());
            $header = $mainFile ? $this->readPluginHeader($mainFile) : [];

            $plugins[] = [
                'slug' => $pluginDir->getBasename(),
                'name' => $header['Name'] ?? $pluginDir->getBasename(),
                'version' => $header['Version'] ?? 'unknown',
            ];
        }

        return $plugins;
    }

    /** @param array<int, array<string, string>> $plugins */
    public function analyze(string $provider, array $plugins): array
    {
        $warnings = [];
        $provider = strtolower($provider);
        $issues = $this->knownIssues[$provider] ?? [];

        foreach ($plugins as $plugin) {
            $slug = strtolower($plugin['slug']);
            if (isset($issues[$slug])) {
                $warnings[] = $issues[$slug];
            }
        }

        return [
            'compatible' => $warnings === [],
            'warnings' => $warnings,
            'optimizations' => $warnings === [] ? ['Enable object cache', 'Optimize images'] : [],
        ];
    }

    private function locatePluginFile(string $pluginDir): ?string
    {
        foreach (glob($pluginDir . '/*.php') ?: [] as $file) {
            if (basename($file) === basename($pluginDir) . '.php') {
                return $file;
            }
        }

        $files = glob($pluginDir . '/*.php') ?: [];
        return $files[0] ?? null;
    }

    /** @return array<string, string> */
    private function readPluginHeader(string $file): array
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            return [];
        }

        $headers = ['Name', 'Version'];
        $data = [];
        foreach ($headers as $header) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)/i', $contents, $matches)) {
                $data[$header] = trim($matches[1]);
            }
        }

        return $data;
    }
}
