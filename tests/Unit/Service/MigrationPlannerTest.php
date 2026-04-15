<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use WPMigration\Domain\MigrationPlan;
use WPMigration\Service\MigrationPlanner;

final class MigrationPlannerTest extends TestCase
{
    private MigrationPlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new MigrationPlanner();
    }

    public function testPlanCreatesMigrationPlan(): void
    {
        $source = [
            'url' => 'https://source-site.com',
            'path' => '/var/www/source',
        ];
        $destination = [
            'url' => 'https://destination-site.com',
            'provider' => 'pressable',
        ];

        $plan = $this->planner->plan($source, $destination, 'standard');

        $this->assertInstanceOf(MigrationPlan::class, $plan);
    }

    public function testPlanIncludesMigrationId(): void
    {
        $plan = $this->planner->plan([], [], 'standard');

        $this->assertNotEmpty($plan->id());
        $this->assertStringStartsWith('mig_', $plan->id());
    }

    public function testPlanSetsStrategy(): void
    {
        $plan = $this->planner->plan([], [], 'incremental');

        $this->assertSame('incremental', $plan->strategy());
    }

    public function testPlanSetsSourceAndDestination(): void
    {
        $source = ['url' => 'https://source.com'];
        $destination = ['url' => 'https://dest.com'];

        $plan = $this->planner->plan($source, $destination, 'standard');

        $this->assertSame($source, $plan->source());
        $this->assertSame($destination, $plan->destination());
    }

    public function testPlanIncludesOptions(): void
    {
        $options = [
            'ssl_enabled' => true,
            'backup_before' => true,
        ];

        $plan = $this->planner->plan([], [], 'standard', $options);

        $this->assertSame($options, $plan->options());
    }

    public function testPlanToArrayReturnsCompleteData(): void
    {
        $source = ['url' => 'https://source.com'];
        $destination = ['url' => 'https://dest.com'];
        $options = ['ssl_enabled' => true];

        $plan = $this->planner->plan($source, $destination, 'standard', $options);
        $data = $plan->toArray();

        $this->assertArrayHasKey('migration_id', $data);
        $this->assertArrayHasKey('source', $data);
        $this->assertArrayHasKey('destination', $data);
        $this->assertArrayHasKey('strategy', $data);
        $this->assertArrayHasKey('options', $data);
    }
}
