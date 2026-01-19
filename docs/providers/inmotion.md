# InMotion Hosting Migration Guide

Support for InMotion Hosting VPS and dedicated server environments.

## Configuration

```json
{
  "provider": "inmotion",
  "config": {
    "ssh_host": "ssh.inmotionhosting.com",
    "ssh_user": "your-username",
    "ssh_key": "/path/to/private/key",
    "php_version": "8.0"
  }
}
```

## Usage

```bash
php wp-migrate create --provider inmotion --source example.com
```
