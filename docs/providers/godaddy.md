# GoDaddy Preset Guide

This preset captures configuration and compatibility hints for GoDaddy shared or managed WordPress targets. It does not perform GoDaddy account or hosting API actions.

## Configuration

```json
{
  "provider": "godaddy",
  "config": {
    "ftp_host": "ftp.godaddy.com",
    "php_version": "7.4",
    "ssl_enabled": true
  }
}
```

## Usage

```bash
php wp-migrate create --provider godaddy --source ./site
```
