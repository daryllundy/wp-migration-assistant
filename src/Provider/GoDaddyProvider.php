<?php

declare(strict_types=1);

namespace WPMigration\Provider;

final class GoDaddyProvider extends HostingProvider
{
    public function getName(): string
    {
        return 'GoDaddy';
    }

    public function getSlug(): string
    {
        return 'godaddy';
    }
}
