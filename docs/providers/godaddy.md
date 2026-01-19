# GoDaddy Migration Guide

Support for GoDaddy managed and shared WordPress hosting environments.

## Configuration

```json
{
  "provider": "godaddy",
  "config": {
    "ftp_host": "ftp.godaddy.com",
    "ftp_user": "your-username",
    "ftp_pass": "your-password",
    "php_version": "7.4",
    "ssl_enabled": true
  }
}
```

## Usage

```bash
php wp-migrate create --provider godaddy --source example.com
```
