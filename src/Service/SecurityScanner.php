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
        $findings = [];
        if (!is_dir($path)) {
            return [
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

            if (preg_match('/(eval\(|base64_decode\(|shell_exec\(|passthru\()/i', $contents)) {
                $findings[] = [
                    'file' => $file->getPathname(),
                    'issue' => 'Suspicious function usage',
                ];
            }
        }

        return [
            'path' => $path,
            'issues' => $findings,
            'status' => $findings === [] ? 'clean' : 'warning',
        ];
    }
}
