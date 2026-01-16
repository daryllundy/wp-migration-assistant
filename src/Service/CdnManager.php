<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Support\JsonFile;

final class CdnManager
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /** @param array<string, mixed> $config */
    public function configure(string $provider, array $config): void
    {
        $payload = JsonFile::read($this->storagePath);
        $payload[$provider] = $config;
        JsonFile::write($this->storagePath, $payload);
    }
}
