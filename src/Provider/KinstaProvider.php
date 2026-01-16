<?php

declare(strict_types=1);

namespace WPMigration\Provider;

final class KinstaProvider extends HostingProvider
{
    public function getName(): string
    {
        return 'Kinsta';
    }

    public function getSlug(): string
    {
        return 'kinsta';
    }

    protected function providerDefaults(): array
    {
        return [
            'optimization' => [
                'kinsta_cache' => true,
                'object_cache' => true,
                'database_optimization' => true,
            ],
        ];
    }
}
