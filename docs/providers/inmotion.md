# InMotion Preset Guide

This preset captures configuration and compatibility hints for InMotion VPS or dedicated targets. It does not provision servers or manage remote deployments.

## Configuration

```json
{
  "provider": "inmotion",
  "config": {
    "ssh_host": "ssh.inmotionhosting.com",
    "ssh_key": "/path/to/private/key",
    "php_version": "8.0"
  }
}
```

## Usage

```bash
php wp-migrate create --provider inmotion --source ./site
```
