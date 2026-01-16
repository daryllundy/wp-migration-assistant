<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Support\JsonFile;

final class DnsManager
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /** @param array<string, string> $record */
    public function updateRecord(string $domain, array $record): void
    {
        $records = JsonFile::read($this->storagePath);
        $records[$domain] = $record;
        JsonFile::write($this->storagePath, $records);
    }
}
