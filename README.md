# WP Migration Assistant

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-green.svg)
![Symfony](https://img.shields.io/badge/Symfony-6.0+-red.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)
![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)
![Status](https://img.shields.io/badge/status-production--ready-success.svg)

**Zero-downtime WordPress migration tool with hosting provider profiles and automated deployment.**

Built for enterprise hosting environments and large-scale WordPress migrations.

## 🚀 Features

### Migration Management
- **Zero-downtime migrations** for sites under 1GB
- **Incremental sync** for large database migrations
- **Automated rollback** with one-click recovery
- **Pre-flight compatibility checks** before migration
- **Real-time migration progress** with detailed logging

### Hosting Provider Support
- **[Pressable](docs/providers/pressable.md)** - Optimized for managed WordPress hosting
- **[Kinsta](docs/providers/kinsta.md)** - Google Cloud Platform infrastructure
- **[GoDaddy](docs/providers/godaddy.md)** - Shared and managed hosting support
- **[InMotion](docs/providers/inmotion.md)** - VPS and dedicated server support
- **[Custom](docs/providers/custom.md)** - Extensible hosting provider framework

### Advanced Features
- **DNS automation** with automatic propagation
- **SSL certificate management** with Let's Encrypt integration
- **Database optimization** during migration process
- **Plugin compatibility analysis** with automatic updates
- **Media file optimization** with CDN integration

## 🔄 Migration Process

![Migration Demo](./demo/migration-demo.gif)

*Zero-downtime WordPress migration with real-time progress tracking*

## 🛠️ Installation

### Prerequisites
- PHP 8.0+
- Composer
- MySQL 8.0+
- WordPress 5.0+
- SSH access to source and destination servers

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
# Analyze source site
php wp-migrate analyze --source-url https://example.com

# Create migration plan
php wp-migrate plan --source example.com --destination pressable.com

# Execute migration
php wp-migrate migrate --plan migration-plan.json

# Monitor migration progress
php wp-migrate status --migration-id 12345
```

### Provider-Specific Migrations
For detailed configuration and usage for specific providers, please refer to the documentation:

| Provider | Description | Documentation |
|----------|-------------|---------------|
| **Pressable** | Managed WordPress Hosting | [View Docs](docs/providers/pressable.md) |
| **Kinsta** | Managed WordPress Hosting | [View Docs](docs/providers/kinsta.md) |
| **GoDaddy** | Shared & Managed Hosting | [View Docs](docs/providers/godaddy.md) |
| **InMotion** | VPS & Dedicated | [View Docs](docs/providers/inmotion.md) |
| **Custom** | Custom Implementations | [View Docs](docs/providers/custom.md) |

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

