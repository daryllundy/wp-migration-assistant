<?php

declare(strict_types=1);

namespace WPMigration\Provider;

final class CustomProvider extends HostingProvider
{
    public function getName(): string
    {
        return 'Custom Provider';
    }

    public function getSlug(): string
    {
        return 'custom';
    }
}
