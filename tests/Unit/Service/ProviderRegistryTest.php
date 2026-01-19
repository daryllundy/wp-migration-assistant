<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Service;

use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPMigration\Provider\HostingProvider;
use WPMigration\Provider\PressableProvider;
use WPMigration\Service\PluginCompatibilityAnalyzer;
use WPMigration\Service\ProviderRegistry;
use WPMigration\Service\SiteAnalyzer;

final class ProviderRegistryTest extends TestCase
{
    private ProviderRegistry $registry;

    protected function setUp(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $pluginAnalyzer = new PluginCompatibilityAnalyzer();
        $siteAnalyzer = new SiteAnalyzer($httpClient, $pluginAnalyzer);

        $this->registry = new ProviderRegistry($siteAnalyzer, $pluginAnalyzer);
    }

    public function testAllReturnsAllProviders(): void
    {
        $providers = $this->registry->all();

        $this->assertCount(5, $providers);
        $this->assertArrayHasKey('pressable', $providers);
        $this->assertArrayHasKey('kinsta', $providers);
        $this->assertArrayHasKey('godaddy', $providers);
        $this->assertArrayHasKey('inmotion', $providers);
        $this->assertArrayHasKey('custom', $providers);
    }

    public function testGetReturnsPressableProvider(): void
    {
        $provider = $this->registry->get('pressable');

        $this->assertInstanceOf(PressableProvider::class, $provider);
        $this->assertSame('Pressable', $provider->getName());
        $this->assertSame('pressable', $provider->getSlug());
    }

    public function testGetIsCaseInsensitive(): void
    {
        $provider1 = $this->registry->get('Pressable');
        $provider2 = $this->registry->get('PRESSABLE');

        $this->assertInstanceOf(PressableProvider::class, $provider1);
        $this->assertInstanceOf(PressableProvider::class, $provider2);
    }

    public function testGetThrowsExceptionForUnknownProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider: nonexistent');

        $this->registry->get('nonexistent');
    }

    public function testAllProvidersExtendHostingProvider(): void
    {
        foreach ($this->registry->all() as $slug => $provider) {
            $this->assertInstanceOf(
                HostingProvider::class,
                $provider,
                sprintf('Provider "%s" should extend HostingProvider', $slug)
            );
        }
    }

    public function testAllProvidersHaveNameAndSlug(): void
    {
        foreach ($this->registry->all() as $slug => $provider) {
            $this->assertNotEmpty(
                $provider->getName(),
                sprintf('Provider "%s" should have a name', $slug)
            );
            $this->assertSame(
                $slug,
                $provider->getSlug(),
                sprintf('Provider slug should match registry key for "%s"', $slug)
            );
        }
    }
}
