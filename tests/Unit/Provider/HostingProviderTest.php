<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Provider;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use WPMigration\Provider\HostingProvider;
use WPMigration\Service\PluginCompatibilityAnalyzer;
use WPMigration\Service\SiteAnalyzer;

final class HostingProviderTest extends TestCase
{
    public function testValidateCompatibilityFromAnalysisUsesProvidedAnalysis(): void
    {
        $provider = new class(
            new SiteAnalyzer($this->createMock(ClientInterface::class), new PluginCompatibilityAnalyzer()),
            new PluginCompatibilityAnalyzer()
        ) extends HostingProvider {
            public function getName(): string
            {
                return 'Test';
            }

            public function getSlug(): string
            {
                return 'pressable';
            }
        };

        $analysis = [
            'plugins' => [
                ['slug' => 'w3-total-cache', 'name' => 'W3 Total Cache', 'version' => '1.0.0'],
            ],
        ];

        $result = $provider->validateCompatibilityFromAnalysis($analysis);

        $this->assertSame($analysis, $result['analysis']);
        $this->assertFalse($result['compatibility']['pressable']['compatible']);
    }
}
