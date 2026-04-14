# WP Migration Assistant

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-green.svg)
![Symfony](https://img.shields.io/badge/Symfony-6.0+-red.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)
![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)
![Status](https://img.shields.io/badge/status-prototype-orange.svg)

**Local-first WordPress migration CLI with provider presets, backup tooling, and migration bookkeeping.**

Built as a practical prototype for local and self-managed WordPress migration workflows.

## 🚀 Features

### Migration Management
- **Plan-driven migrations** for local or self-managed workflows
- **Incremental file sync mode** for repeated local sync passes
- **Automated rollback** from local backup artifacts
- **Pre-flight compatibility checks** based on local inspection and plugin heuristics
- **Step-based progress tracking** with persisted logs and status files

### Hosting Provider Support
- **[Pressable](docs/providers/pressable.md)** - Provider preset and compatibility heuristics
- **[Kinsta](docs/providers/kinsta.md)** - Provider preset and compatibility heuristics
- **[GoDaddy](docs/providers/godaddy.md)** - Provider preset and compatibility heuristics
- **[InMotion](docs/providers/inmotion.md)** - Provider preset and compatibility heuristics
- **[Custom](docs/providers/custom.md)** - Extensible provider preset framework

### Advanced Features
- **Local DNS manifest updates** for planned cutovers
- **Local certificate issuance helpers** using `certbot` or self-signed certs
- **Database optimization** during migration process
- **Plugin compatibility analysis** using provider-specific warning rules
- **Media file optimization** for local uploads directories

## 🔄 Migration Process

![Migration Demo](./demo/migration-demo.gif)

*Prototype migration flow with step-based progress tracking*

## 🛠️ Installation

### Prerequisites
- PHP 8.0+
- Composer
- MySQL 8.0+
- WordPress 5.0+
- Local filesystem access to the source and destination sites you want to migrate
- Optional: `rsync`, `mysql`, `certbot`, and `wkhtmltopdf`

### Quick Start
```bash
# Clone and install
git clone https://www.github.com/daryllundy/wp-migration-assistant
cd wp-migration-assistant
composer install

# Configure environment
cp .env.example .env
# Edit .env with your configuration

# Run initial setup
php wp-migrate setup
```

## 🎯 Usage

### Common Migration Commands
```bash
# Analyze a local site or a remote URL
php wp-migrate analyze --source ./site
php wp-migrate analyze --source-url https://example.com

# Create a local migration plan
php wp-migrate plan --source ./site --destination ./site-copy

# Execute a migration from a saved plan
php wp-migrate migrate --plan migration-plan.json

# Monitor migration progress
php wp-migrate status --migration-id 12345
```

### Provider Presets
Provider docs describe preset configuration, compatibility heuristics, and suggested flags. They do not provide direct hosted-provider API integration:

| Provider | Description | Documentation |
|----------|-------------|---------------|
| **Pressable** | Preset for managed WordPress targets | [View Docs](docs/providers/pressable.md) |
| **Kinsta** | Preset for managed WordPress targets | [View Docs](docs/providers/kinsta.md) |
| **GoDaddy** | Preset for shared or managed WordPress targets | [View Docs](docs/providers/godaddy.md) |
| **InMotion** | Preset for VPS or dedicated WordPress targets | [View Docs](docs/providers/inmotion.md) |
| **Custom** | Custom preset implementations | [View Docs](docs/providers/custom.md) |

## ⚠️ Scope Notes

- The CLI is strongest for local-to-local migrations and migration bookkeeping.
- DNS and CDN commands write local manifests rather than calling provider APIs.
- SSL helpers can invoke local `certbot` when available, otherwise they generate self-signed certificates.
- Incremental and staging workflows are guided CLI flows, not managed zero-downtime orchestration.

## 🔧 Development

### Local Development
```bash
# Install development dependencies
composer install --dev

# Run tests
php vendor/bin/phpunit

# Run code analysis
php vendor/bin/phpstan analyze

# Run code formatting
composer run cs-fix
```
