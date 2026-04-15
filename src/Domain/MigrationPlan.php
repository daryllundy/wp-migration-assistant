<?php

declare(strict_types=1);

namespace WPMigration\Domain;

final class MigrationPlan
{
    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    public function id(): string
    {
        return (string) ($this->data['migration_id'] ?? '');
    }

    public function strategy(): string
    {
        return (string) ($this->data['strategy'] ?? 'standard');
    }

    /** @return array<string, mixed> */
    public function source(): array
    {
        return (array) ($this->data['source'] ?? []);
    }

    /** @return array<string, mixed> */
    public function destination(): array
    {
        return (array) ($this->data['destination'] ?? []);
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        return (array) ($this->data['options'] ?? []);
    }
}
