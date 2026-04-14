<?php

declare(strict_types=1);

namespace WPMigration\Service;

use GuzzleHttp\ClientInterface;
use WPMigration\Support\FilesystemHelper;

final class SiteAnalyzer
{
    private ClientInterface $httpClient;
    private PluginCompatibilityAnalyzer $pluginAnalyzer;

    public function __construct(ClientInterface $httpClient, PluginCompatibilityAnalyzer $pluginAnalyzer)
    {
        $this->httpClient = $httpClient;
        $this->pluginAnalyzer = $pluginAnalyzer;
    }

    /** @return array<string, mixed> */
    public function analyze(string $source): array
    {
        $isUrl = filter_var($source, FILTER_VALIDATE_URL) !== false;
        if ($isUrl) {
            return $this->analyzeRemote($source);
        }

        return $this->analyzeLocal($source);
    }

    /** @return array<string, mixed> */
    private function analyzeLocal(string $path): array
    {
        $wpConfig = rtrim($path, '/') . '/wp-config.php';
        $wpVersion = $this->extractWpVersion(rtrim($path, '/') . '/wp-includes/version.php');
        $uploadsPath = rtrim($path, '/') . '/wp-content/uploads';
        $metrics = FilesystemHelper::directoryMetrics($path, $uploadsPath);
        $plugins = $this->pluginAnalyzer->listPlugins($path);

        return [
            'source_type' => 'local',
            'path' => $path,
            'wordpress_version' => $wpVersion,
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->detectMysqlVersion(),
            'total_size' => FilesystemHelper::formatBytes($metrics['total_size']),
            'database_size' => null,
            'media_files' => FilesystemHelper::formatBytes($metrics['matched_size']),
            'wp_config_found' => file_exists($wpConfig),
            'plugins' => $plugins,
        ];
    }

    /** @return array<string, mixed> */
    private function analyzeRemote(string $url): array
    {
        $response = $this->httpClient->request('GET', $url, ['http_errors' => false]);
        $body = (string) $response->getBody();

        $wpVersion = null;
        if (preg_match('/<meta name="generator" content="WordPress ([^"]+)"/i', $body, $matches)) {
            $wpVersion = $matches[1];
        }

        $wpJson = rtrim($url, '/') . '/wp-json';
        $jsonResponse = $this->httpClient->request('GET', $wpJson, ['http_errors' => false]);
        if ($jsonResponse->getStatusCode() === 200) {
            $json = json_decode((string) $jsonResponse->getBody(), true);
            if (is_array($json) && isset($json['generator'])) {
                $wpVersion = $json['generator'];
            }
        }

        return [
            'source_type' => 'remote',
            'url' => $url,
            'wordpress_version' => $wpVersion,
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->detectMysqlVersion(),
            'total_size' => null,
            'database_size' => null,
            'media_files' => null,
            'wp_config_found' => null,
            'plugins' => [],
        ];
    }

    private function extractWpVersion(string $versionFile): ?string
    {
        if (!file_exists($versionFile)) {
            return null;
        }

        $contents = file_get_contents($versionFile);
        if ($contents === false) {
            return null;
        }

        if (preg_match("/\$wp_version\s*=\s*'([^']+)'/", $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function detectMysqlVersion(): ?string
    {
        if (function_exists('mysqli_get_client_info')) {
            return mysqli_get_client_info();
        }

        return null;
    }
}
