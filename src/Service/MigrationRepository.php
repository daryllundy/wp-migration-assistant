<?php

declare(strict_types=1);

namespace WPMigration\Service;

use DateTimeImmutable;
use WPMigration\Support\JsonFile;

final class MigrationRepository
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): string
    {
        $id = $payload['migration_id'] ?? ('mig_' . bin2hex(random_bytes(4)));
        $payload['migration_id'] = $id;
        $payload['created_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);
        $payload['updated_at'] = $payload['created_at'];

        $this->save($id, $payload);

        return $id;
    }

    /** @param array<string, mixed> $payload */
    public function update(string $id, array $payload): void
    {
        $existing = $this->get($id);
        $merged = array_merge($existing, $payload);
        $merged['migration_id'] = $id;
        $merged['updated_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        $this->save($id, $merged);
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        return JsonFile::read($this->pathFor($id));
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if (!is_dir($this->storagePath)) {
            return [];
        }

        $files = glob($this->storagePath . '/*.json') ?: [];
        $records = [];
        foreach ($files as $file) {
            $records[] = JsonFile::read($file);
        }

        return $records;
    }

    /** @param array<string, mixed> $payload */
    private function save(string $id, array $payload): void
    {
        JsonFile::write($this->pathFor($id), $payload);
    }

    private function pathFor(string $id): string
    {
        return $this->storagePath . '/' . $id . '.json';
    }
}
