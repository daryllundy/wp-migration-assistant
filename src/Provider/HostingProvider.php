<?php

declare(strict_types=1);

namespace WPMigration\Provider;

use WPMigration\Service\SiteAnalyzer;
use WPMigration\Service\PluginCompatibilityAnalyzer;

abstract class HostingProvider
{
    protected SiteAnalyzer $siteAnalyzer;
    protected PluginCompatibilityAnalyzer $pluginAnalyzer;

    public function __construct(SiteAnalyzer $siteAnalyzer, PluginCompatibilityAnalyzer $pluginAnalyzer)
    {
        $this->siteAnalyzer = $siteAnalyzer;
        $this->pluginAnalyzer = $pluginAnalyzer;
    }

    abstract public function getName(): string;

    abstract public function getSlug(): string;

    /** @return array<string, mixed> */
    public function validateCompatibility(string $source): array
    {
        $analysis = $this->siteAnalyzer->analyze($source);
        return $this->validateCompatibilityFromAnalysis($analysis);
    }

    /** @param array<string, mixed> $analysis */
    /** @return array<string, mixed> */
    public function validateCompatibilityFromAnalysis(array $analysis): array
    {
        $pluginCheck = $this->pluginAnalyzer->analyze($this->getSlug(), $analysis['plugins'] ?? []);

        return [
            'analysis' => $analysis,
            'compatibility' => [
                $this->getSlug() => $pluginCheck,
            ],
        ];
    }

    /** @param array<string, mixed> $config */
    public function buildConfig(array $config): array
    {
        return array_merge([
            'provider' => $this->getSlug(),
            'config' => $config,
        ], $this->providerDefaults());
    }

    /** @return array<string, mixed> */
    protected function providerDefaults(): array
    {
        return [];
    }
}
