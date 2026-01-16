<?php

declare(strict_types=1);

namespace WPMigration\Provider;

final class InMotionProvider extends HostingProvider
{
    public function getName(): string
    {
        return 'InMotion';
    }

    public function getSlug(): string
    {
        return 'inmotion';
    }
}
