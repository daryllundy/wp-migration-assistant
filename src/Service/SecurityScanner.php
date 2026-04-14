<?php

declare(strict_types=1);

namespace WPMigration\Service;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class SecurityScanner
{
    /** @return array<string, mixed> */
    public function scan(string $path): array
    {
        return $this->scanSecurity($path);
    }

    /** @return array<string, mixed> */
    public function scanSecurity(string $path): array
    {
        return $this->runScan(
            $path,
            'security',
            [
                [
                    'pattern' => '/(shell_exec\(|passthru\(|proc_open\(|popen\()/i',
                    'issue' => 'Command execution primitive detected',
                    'severity' => 'high',
                ],
                [
                    'pattern' => '/(eval\()/i',
                    'issue' => 'Dynamic code execution detected',
                    'severity' => 'high',
                ],
                [
                    'pattern' => '/(WP_DEBUG\s*[,\)]\s*true|define\(\s*[\'"]WP_DEBUG[\'"]\s*,\s*true\s*\))/i',
                    'issue' => 'Debug mode appears enabled',
                    'severity' => 'medium',
                ],
            ]
        );
    }

    /** @return array<string, mixed> */
    public function scanMalware(string $path): array
    {
        return $this->runScan(
            $path,
            'malware',
            [
                [
                    'pattern' => '/(base64_decode\(|gzinflate\(|str_rot13\(|assert\()/i',
                    'issue' => 'Obfuscation or payload execution pattern detected',
                    'severity' => 'high',
                ],
                [
                    'pattern' => '/[A-Za-z0-9+\/]{200,}={0,2}/',
                    'issue' => 'Large encoded blob detected',
                    'severity' => 'medium',
                ],
                [
                    'pattern' => '/preg_replace\s*\(\s*["\'][^"\']*\/e/i',
                    'issue' => 'Legacy code execution via preg_replace /e detected',
                    'severity' => 'high',
                ],
            ]
        );
    }

    /** @return array<string, mixed> */
    public function scanVulnerabilities(string $path): array
    {
        $issues = [];
        if (!is_dir($path)) {
            return [
                'scanner' => 'vulnerability',
                'path' => $path,
                'issues' => ['Path not found'],
            ];
        }

        $versionFile = rtrim($path, '/') . '/wp-includes/version.php';
        if (file_exists($versionFile)) {
            $contents = file_get_contents($versionFile);
            if ($contents !== false && preg_match("/\$wp_version\s*=\s*'([^']+)'/", $contents, $matches)) {
                $version = $matches[1];
                if (version_compare($version, '6.3', '<')) {
                    $issues[] = [
                        'file' => $versionFile,
                        'issue' => 'WordPress core version is behind the baseline used by this scanner',
                        'severity' => 'medium',
                        'evidence' => $version,
                    ];
                }
            }
        }

        $pluginDir = rtrim($path, '/') . '/wp-content/plugins';
        if (is_dir($pluginDir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                if ($contents === false) {
                    continue;
                }

                if (preg_match('/\$_(GET|POST|REQUEST)\s*\[[^\]]+\].*(include|require)(_once)?\s*\(/i', $contents)) {
                    $issues[] = [
                        'file' => $file->getPathname(),
                        'issue' => 'User input appears to influence a file include path',
                        'severity' => 'high',
                    ];
                }

                if (preg_match('/move_uploaded_file\s*\(/i', $contents) && !preg_match('/wp_handle_upload\s*\(/i', $contents)) {
                    $issues[] = [
                        'file' => $file->getPathname(),
                        'issue' => 'Direct file upload handling detected without WordPress helper usage',
                        'severity' => 'medium',
                    ];
                }
            }
        }

        return [
            'scanner' => 'vulnerability',
            'path' => $path,
            'issues' => $issues,
            'status' => $issues === [] ? 'clean' : 'warning',
        ];
    }

    /**
     * @param array<int, array{pattern: string, issue: string, severity: string}> $rules
     * @return array<string, mixed>
     */
    private function runScan(string $path, string $scanner, array $rules): array
    {
        $findings = [];
        if (!is_dir($path)) {
            return [
                'scanner' => $scanner,
                'path' => $path,
                'issues' => ['Path not found'],
            ];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }

            foreach ($rules as $rule) {
                if (!preg_match($rule['pattern'], $contents)) {
                    continue;
                }

                $findings[] = [
                    'file' => $file->getPathname(),
                    'issue' => $rule['issue'],
                    'severity' => $rule['severity'],
                ];
            }
        }

        return [
            'scanner' => $scanner,
            'path' => $path,
            'issues' => $findings,
            'status' => $findings === [] ? 'clean' : 'warning',
        ];
    }
}
