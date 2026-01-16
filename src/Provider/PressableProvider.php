<?php

declare(strict_types=1);

namespace WPMigration\Provider;

final class PressableProvider extends HostingProvider
{
    public function getName(): string
    {
        return 'Pressable';
    }

    public function getSlug(): string
    {
        return 'pressable';
    }

    protected function providerDefaults(): array
    {
        return [
            'optimization' => [
                'object_cache' => true,
                'page_cache' => true,
                'database_optimization' => true,
            ],
        ];
    }
}
