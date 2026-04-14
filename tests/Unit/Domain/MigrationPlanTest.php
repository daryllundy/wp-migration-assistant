<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPMigration\Domain\MigrationPlan;

final class MigrationPlanTest extends TestCase
{
    public function testIdReturnsEmptyStringWhenMissing(): void
    {
        $plan = new MigrationPlan([]);

        $this->assertSame('', $plan->id());
    }

    public function testIdReturnsMigrationId(): void
    {
        $plan = new MigrationPlan(['migration_id' => 'mig_12345']);

        $this->assertSame('mig_12345', $plan->id());
    }

    public function testStrategyDefaultsToZeroDowntime(): void
    {
        $plan = new MigrationPlan([]);

        $this->assertSame('standard', $plan->strategy());
    }

    public function testStrategyReturnsSetValue(): void
    {
        $plan = new MigrationPlan(['strategy' => 'incremental']);

        $this->assertSame('incremental', $plan->strategy());
    }

    public function testSourceReturnsEmptyArrayWhenMissing(): void
    {
        $plan = new MigrationPlan([]);

        $this->assertSame([], $plan->source());
    }

    public function testSourceReturnsSetValue(): void
    {
        $source = ['url' => 'https://example.com', 'path' => '/var/www'];
        $plan = new MigrationPlan(['source' => $source]);

        $this->assertSame($source, $plan->source());
    }

    public function testDestinationReturnsEmptyArrayWhenMissing(): void
    {
        $plan = new MigrationPlan([]);

        $this->assertSame([], $plan->destination());
    }

    public function testDestinationReturnsSetValue(): void
    {
        $destination = ['url' => 'https://new-site.com', 'provider' => 'pressable'];
        $plan = new MigrationPlan(['destination' => $destination]);

        $this->assertSame($destination, $plan->destination());
    }

    public function testOptionsReturnsEmptyArrayWhenMissing(): void
    {
        $plan = new MigrationPlan([]);

        $this->assertSame([], $plan->options());
    }

    public function testOptionsReturnsSetValue(): void
    {
        $options = ['ssl_enabled' => true, 'backup_before' => true];
        $plan = new MigrationPlan(['options' => $options]);

        $this->assertSame($options, $plan->options());
    }

    public function testToArrayReturnsFullData(): void
    {
        $data = [
            'migration_id' => 'mig_abc123',
            'source' => ['url' => 'https://source.com'],
            'destination' => ['url' => 'https://dest.com'],
            'strategy' => 'incremental',
            'options' => ['backup_before' => true],
        ];
        $plan = new MigrationPlan($data);

        $this->assertSame($data, $plan->toArray());
    }
}
