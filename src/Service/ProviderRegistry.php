<?php

declare(strict_types=1);

namespace WPMigration\Service;

use WPMigration\Provider\CustomProvider;
use WPMigration\Provider\GoDaddyProvider;
use WPMigration\Provider\HostingProvider;
use WPMigration\Provider\InMotionProvider;
use WPMigration\Provider\KinstaProvider;
use WPMigration\Provider\PressableProvider;

final class ProviderRegistry
{
    /** @var array<string, HostingProvider> */
    private array $providers;

    public function __construct(SiteAnalyzer $siteAnalyzer, PluginCompatibilityAnalyzer $pluginAnalyzer)
    {
        $this->providers = [
            'pressable' => new PressableProvider($siteAnalyzer, $pluginAnalyzer),
            'kinsta' => new KinstaProvider($siteAnalyzer, $pluginAnalyzer),
            'godaddy' => new GoDaddyProvider($siteAnalyzer, $pluginAnalyzer),
            'inmotion' => new InMotionProvider($siteAnalyzer, $pluginAnalyzer),
            'custom' => new CustomProvider($siteAnalyzer, $pluginAnalyzer),
        ];
    }

    /** @return array<string, HostingProvider> */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $slug): HostingProvider
    {
        $slug = strtolower($slug);
        if (!isset($this->providers[$slug])) {
            throw new \InvalidArgumentException('Unknown provider: ' . $slug);
        }

        return $this->providers[$slug];
    }
}
