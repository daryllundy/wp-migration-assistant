# Kinsta Preset Guide

This preset captures Kinsta-oriented defaults and compatibility warnings. It does not call Kinsta APIs or create sites automatically.

## Configuration

Use the following JSON structure for Kinsta-oriented plans:

```json
{
  "provider": "kinsta",
  "config": {
    "php_version": "8.0",
    "optimization": {
      "kinsta_cache": true,
      "object_cache": true,
      "database_optimization": true
    }
  }
}
```

## What This Preset Does

- **Preset Defaults**: Carries Kinsta-flavored optimization flags into the generated plan.
- **Compatibility Heuristics**: Surfaces a small set of plugin warnings during analysis.
- **Manual Handoff Friendly**: Keeps provider-specific settings in a plan file you can use alongside your own deployment process.

## Usage

```bash
php wp-migrate create --provider kinsta --source ./site
```
