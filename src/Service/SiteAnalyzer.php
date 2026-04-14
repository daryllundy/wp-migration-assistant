<?php

declare(strict_types=1);

namespace WPMigration\Service;

use Doctrine\DBAL\DriverManager;
use GuzzleHttp\ClientInterface;
use WPMigration\Support\FilesystemHelper;

final class SiteAnalyzer
{
    private ClientInterface $httpClient;
    private PluginCompatibilityAnalyzer $pluginAnalyzer;
    private WordPressConfigParser $configParser;

    public function __construct(
        ClientInterface $httpClient,
        PluginCompatibilityAnalyzer $pluginAnalyzer,
        ?WordPressConfigParser $configParser = null
    )
    {
        $this->httpClient = $httpClient;
        $this->pluginAnalyzer = $pluginAnalyzer;
        $this->configParser = $configParser ?? new WordPressConfigParser();
    }

    /** @return array<string, mixed> */
    public function analyze(string $source, array $options = []): array
    {
        $isUrl = filter_var($source, FILTER_VALIDATE_URL) !== false;
        if ($isUrl) {
            return $this->analyzeRemote($source, $options);
        }

        return $this->analyzeLocal($source, $options);
    }

    /** @return array<string, mixed> */
    private function analyzeLocal(string $path, array $options): array
    {
        $wpConfig = rtrim($path, '/') . '/wp-config.php';
        $wpVersion = $this->extractWpVersion(rtrim($path, '/') . '/wp-includes/version.php');
        $uploadsPath = rtrim($path, '/') . '/wp-content/uploads';
        $metrics = FilesystemHelper::directoryMetrics($path, $uploadsPath);
        $includePlugins = (bool) ($options['include_plugins'] ?? true);
        $includeDatabase = (bool) ($options['include_database'] ?? false);
        $includePerformance = (bool) ($options['include_performance'] ?? false);
        $plugins = $includePlugins ? $this->pluginAnalyzer->listPlugins($path) : [];
        $database = $includeDatabase ? $this->inspectLocalDatabase($path) : null;
        $performance = $includePerformance ? $this->analyzeLocalPerformance($metrics, $plugins) : null;

        $analysis = [
            'source_type' => 'local',
            'path' => $path,
            'wordpress_version' => $wpVersion,
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->detectMysqlVersion(),
            'total_size' => FilesystemHelper::formatBytes($metrics['total_size']),
            'database_size' => $database['size_human'] ?? null,
            'media_files' => FilesystemHelper::formatBytes($metrics['matched_size']),
            'wp_config_found' => file_exists($wpConfig),
        ];

        if ($includePlugins) {
            $analysis['plugins'] = $plugins;
            $analysis['plugin_count'] = count($plugins);
        }

        if ($database !== null) {
            $analysis['database'] = $database;
        }

        if ($performance !== null) {
            $analysis['performance'] = $performance;
        }

        return $analysis;
    }

    /** @return array<string, mixed> */
    private function analyzeRemote(string $url, array $options): array
    {
        $includePerformance = (bool) ($options['include_performance'] ?? false);
        $startedAt = microtime(true);
        $response = $this->httpClient->request('GET', $url, ['http_errors' => false]);
        $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
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

        $analysis = [
            'source_type' => 'remote',
            'url' => $url,
            'wordpress_version' => $wpVersion,
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->detectMysqlVersion(),
            'total_size' => null,
            'database_size' => null,
            'media_files' => null,
            'wp_config_found' => null,
        ];

        if ((bool) ($options['include_plugins'] ?? true)) {
            $analysis['plugins'] = [];
            $analysis['plugin_count'] = 0;
        }

        if ($includePerformance) {
            $analysis['performance'] = [
                'response_time_ms' => $responseTimeMs,
                'homepage_bytes' => strlen($body),
                'homepage_status' => $response->getStatusCode(),
                'wp_json_status' => $jsonResponse->getStatusCode(),
            ];
        }

        if ((bool) ($options['include_database'] ?? false)) {
            $analysis['database'] = [
                'status' => 'unavailable',
                'reason' => 'Remote database inspection is not supported by this CLI.',
            ];
        }

        return $analysis;
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

    /** @return array<string, mixed> */
    private function inspectLocalDatabase(string $path): array
    {
        $config = $this->configParser->parseDatabaseConfig($path);
        if ($config === []) {
            return [
                'status' => 'unavailable',
                'reason' => 'Database configuration could not be read from wp-config.php.',
            ];
        }

        try {
            $connection = DriverManager::getConnection([
                'dbname' => $config['name'] ?? '',
                'user' => $config['user'] ?? '',
                'password' => $config['password'] ?? '',
                'host' => $config['host'] ?? 'localhost',
                'driver' => 'pdo_mysql',
            ]);

            $tableCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?', [$config['name'] ?? '']);
            $sizeBytes = (int) $connection->fetchOne(
                'SELECT COALESCE(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = ?',
                [$config['name'] ?? '']
            );

            return [
                'status' => 'available',
                'database_name' => $config['name'] ?? '',
                'host' => $config['host'] ?? 'localhost',
                'table_count' => $tableCount,
                'size_bytes' => $sizeBytes,
                'size_human' => FilesystemHelper::formatBytes($sizeBytes),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'database_name' => $config['name'] ?? '',
                'host' => $config['host'] ?? 'localhost',
                'reason' => $exception->getMessage(),
            ];
        }
    }

    /** @param array<string, int> $metrics */
    /** @param array<int, array<string, string>> $plugins */
    /** @return array<string, mixed> */
    private function analyzeLocalPerformance(array $metrics, array $plugins): array
    {
        $totalSize = $metrics['total_size'];
        $mediaSize = $metrics['matched_size'];

        return [
            'plugin_count' => count($plugins),
            'uploads_share_percent' => $totalSize > 0 ? round(($mediaSize / $totalSize) * 100, 2) : 0.0,
            'estimated_sync_profile' => $totalSize >= 1024 * 1024 * 1024 ? 'large' : 'standard',
            'total_size_bytes' => $totalSize,
            'media_size_bytes' => $mediaSize,
        ];
    }
}
