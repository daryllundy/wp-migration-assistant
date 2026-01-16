<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Domain\MigrationPlan;

final class MigrationPlanner
{
    /** @param array<string, mixed> $source */
    /** @param array<string, mixed> $destination */
    /** @param array<string, mixed> $options */
    public function plan(array $source, array $destination, string $strategy, array $options = []): MigrationPlan
    {
        $plan = [
            'migration_id' => 'mig_' . bin2hex(random_bytes(4)),
            'source' => $source,
            'destination' => $destination,
            'strategy' => $strategy,
            'options' => $options,
        ];

        return new MigrationPlan($plan);
    }
}
