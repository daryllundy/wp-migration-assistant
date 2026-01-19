# Pressable Migration Guide

Pressable is a managed WordPress hosting provider. The WP Migration Assistant includes specific optimizations and configuration options for Pressable.

## Configuration

To migrate to Pressable, use the following configuration structure in your migration plan or when creating a provider profile.

```json
{
  "provider": "pressable",
  "config": {
    "api_key": "your-pressable-api-key",
    "ssh_key": "/path/to/ssh/key",
    "php_version": "8.0",
    "wordpress_version": "latest",
    "ssl_enabled": true,
    "cdn_enabled": true,
    "optimization": {
      "object_cache": true,
      "page_cache": true,
      "database_optimization": true
    }
  }
}
```

## Features

- **Managed Optimization**: Automatically enables object cache and page cache suitable for Pressable's environment.
- **CDN Integration**: Configures CDN settings automatically.
- **Staging Support**: Seamlessly deploy to Pressable staging environments.

## Integration Examples

### Create Pressable Migration
```bash
php wp-migrate create --provider pressable --source example.com
```

### Planned API Integration
Future versions will support direct API integration:

```php
$pressable = new PressableAPI($apiKey);
$migration = $pressable->createMigration([
    'source_url' => 'https://source-site.com',
    'destination_site' => 'new-site',
    'strategy' => 'zero-downtime'
]);
```
