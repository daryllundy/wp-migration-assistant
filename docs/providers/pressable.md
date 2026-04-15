# Pressable Preset Guide

This preset captures Pressable-oriented defaults and compatibility warnings. It does not call Pressable APIs or provision infrastructure.

## Configuration

Use the following configuration structure in your migration plan or when creating a provider preset.

```json
{
  "provider": "pressable",
  "config": {
    "ssh_key": "/path/to/ssh/key",
    "php_version": "8.0",
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

## What This Preset Does

- **Preset Defaults**: Carries recommended optimization flags into generated plans.
- **Compatibility Heuristics**: Warns about a small set of known plugin conflicts.
- **Optional Local Helpers**: Can trigger local CDN manifest and certificate steps during migration execution.

## Usage

```bash
php wp-migrate create --provider pressable --source ./site
```

The resulting plan is still executed by the local CLI. You must supply any real hosting-side deployment, DNS, or credential steps separately.
