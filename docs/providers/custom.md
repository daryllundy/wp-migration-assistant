# Custom Provider Presets

The CLI supports custom provider presets. A custom provider can supply naming, defaults, and compatibility heuristics, but the current architecture does not include remote provisioning hooks.

## Creating a Custom Provider Preset

Extend `HostingProvider` and override the pieces the CLI actually consumes today.

```php
<?php

use WPMigration\Provider\HostingProvider;
use WPMigration\Service\PluginCompatibilityAnalyzer;
use WPMigration\Service\SiteAnalyzer;

class MyCustomProvider extends HostingProvider
{
    public function getName(): string
    {
        return 'My Custom Hosting';
    }

    protected function providerDefaults(): array
    {
        return [
            'optimization' => [
                'database_optimization' => true,
            ],
        ];
    }
}
```

## Usage

To use a custom provider configuration:

```bash
php wp-migrate create --provider custom --source ./site --config custom-provider.json
```
