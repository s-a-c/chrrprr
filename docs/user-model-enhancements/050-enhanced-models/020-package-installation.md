# Package Installation Guide

This guide provides step-by-step installation instructions for all required packages.

## Installation Order

Install packages in the following order to ensure dependencies are resolved correctly:

1. Core Framework Packages
2. Model Enhancement Packages
3. State & Status Management
4. Filament Plugins
5. Testing & Quality Assurance Packages

## Core Framework Packages

```bash
composer require filament/filament:^5.0
composer require stancl/tenancy:^4.14
```

**Note**: `livewire/livewire:^4.0` should already be installed in Laravel Livewire starter kit.

## Model Enhancement Packages

```bash
# ULID support
composer require symfony/uid:^7.2

# Slug generation and translatable content
composer require cviebrock/eloquent-sluggable:^10.0
composer require spatie/laravel-translatable:^6.4
composer require spatie/laravel-translation-loader:^3.6

# Hierarchical relationships and STI
composer require staudenmeir/laravel-adjacency-list:^10.0
composer require tightenco/parental:^2.9
```

## State & Status Management

```bash
composer require spatie/laravel-model-states:^2.6
composer require spatie/laravel-model-status:^2.3
```

## Data & Validation

```bash
composer require spatie/laravel-data:^4.9
```

## Permissions & Security

```bash
composer require spatie/laravel-permission:^6.11
composer require ukeloop/laravel-impersonatable-guard:^2.0
```

## Auditing & Monitoring

```bash
composer require laravel-auditing/auditing:^16.0
composer require binafy/laravel-user-monitoring:^2.0
composer require spatie/laravel-activitylog:^4.8
```

## Additional Features

```bash
composer require spatie/laravel-tags:^5.0
composer require spatie/laravel-medialibrary:^11.0
composer require spatie/laravel-settings:^3.4
composer require spatie/laravel-backup:^8.17
composer require spatie/laravel-health:^1.38
composer require spatie/laravel-sitemap:^7.1
composer require lakshan-madushanka/commenter:^1.0
```

## Laravel Ecosystem Packages

```bash
composer require laravel/horizon:^6.0
composer require laravel/pennant:^1.18
composer require laravel/pulse:^1.24
composer require laravel/socialite:^5.18
composer require laravel/telescope:^5.22
```

## Filament Plugins

### Add Required Repositories

Add these repositories to your `composer.json`:

```json
"repositories": {
    "filament-shield": {
        "type": "vcs",
        "url": "https://github.com/s-a-c/filament-shield"
    },
    "filament-spatie-laravel-media-library-plugin": {
        "type": "composer",
        "url": "https://composer.filamentphp.com"
    },
    "filament-spatie-laravel-settings-plugin": {
        "type": "composer",
        "url": "https://composer.filamentphp.com"
    }
}
```

### Install Filament Plugins

```bash
composer require bezhansalleh/filament-shield:dev-main
composer require filament/spatie-laravel-media-library-plugin:^4.0
composer require filament/spatie-laravel-settings-plugin:^4.0
composer require lara-zeus/spatie-translatable:^3.0
composer require pxlrbt/filament-activity-log:^3.0
composer require shuvroroy/filament-spatie-laravel-backup:^3.0
composer require shuvroroy/filament-spatie-laravel-health:^3.0
```

## Testing & Quality Assurance Packages (Development)

```bash
# BDD Testing
composer require --dev behat/behat:^3.29
composer require --dev behat/gherkin:^4.16.1
composer require --dev behat/mink:^1.12
composer require --dev behat/mink-extension:^2.6
composer require --dev behat/mink-goutte-driver:^1.4
composer require --dev behat/mink-selenium2-driver:^1.6

# Note: Pest and PHPUnit should already be installed
# Note: PHPStan, Larastan, Psalm, PHPMD, Rector should already be installed
```

## JavaScript/TypeScript Quality Tools

```bash
# Install via npm/bun
bun add -d vitest @vitest/ui @vitest/coverage-v8 @testing-library/jest-dom
bun add -d eslint typescript @typescript-eslint/eslint-plugin @typescript-eslint/parser
bun add -d eslint-plugin-import prettier
```

## Post-Installation Steps

### 1. Publish Package Configurations

```bash
# Tenancy
php artisan vendor:publish --tag=tenancy-config

# Translatable
php artisan vendor:publish --provider="Spatie\Translatable\TranslatableServiceProvider"

# Permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Model States
php artisan vendor:publish --provider="Spatie\ModelStates\ModelStatesServiceProvider"

# Model Status
php artisan vendor:publish --provider="Spatie\ModelStatus\ModelStatusServiceProvider"

# Activity Log
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"

# Media Library
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"

# Settings
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider"

# Backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Health
php artisan vendor:publish --provider="Spatie\Health\HealthServiceProvider"

# Filament
php artisan filament:install

# Filament Shield
php artisan shield:install
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Configure Environment Variables

Add to `.env`:

```env
# Tenancy
TENANCY_IDENTIFICATION_METHOD=domain
TENANCY_DOMAIN_DEFAULT=app.example.com

# Translatable
LOCALES=en_GB,en_US,de_DE,nl_NL,nl_BE
FALLBACK_LOCALE=en_GB

# Media Library
MEDIA_DISK=public
MEDIA_CONVERSIONS_DISK=public
```

### 4. Configure Service Providers

Ensure service providers are registered in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FolioServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    // Tenancy providers are auto-discovered
];
```

## Complete Installation Script

For convenience, here's a complete installation script:

```bash
#!/bin/bash

# Core Framework
composer require filament/filament:^5.0 stancl/tenancy:^4.14

# Model Enhancement
composer require symfony/uid:^7.2 \
    cviebrock/eloquent-sluggable:^10.0 \
    spatie/laravel-translatable:^6.4 \
    spatie/laravel-translation-loader:^3.6 \
    staudenmeir/laravel-adjacency-list:^10.0 \
    tightenco/parental:^2.9

# State & Status
composer require spatie/laravel-model-states:^2.6 \
    spatie/laravel-model-status:^2.3 \
    spatie/laravel-data:^4.9

# Permissions & Security
composer require spatie/laravel-permission:^6.11 \
    ukeloop/laravel-impersonatable-guard:^2.0

# Auditing & Monitoring
composer require laravel-auditing/auditing:^16.0 \
    binafy/laravel-user-monitoring:^2.0 \
    spatie/laravel-activitylog:^4.8

# Additional Features
composer require spatie/laravel-tags:^5.0 \
    spatie/laravel-medialibrary:^11.0 \
    spatie/laravel-settings:^3.4 \
    spatie/laravel-backup:^8.17 \
    spatie/laravel-health:^1.38 \
    spatie/laravel-sitemap:^7.1 \
    lakshan-madushanka/commenter:^1.0

# Laravel Ecosystem
composer require laravel/horizon:^6.0 \
    laravel/pennant:^1.18 \
    laravel/pulse:^1.24 \
    laravel/socialite:^5.18 \
    laravel/telescope:^5.22

# Filament Plugins (after adding repositories)
composer require bezhansalleh/filament-shield:dev-main \
    filament/spatie-laravel-media-library-plugin:^4.0 \
    filament/spatie-laravel-settings-plugin:^4.0 \
    lara-zeus/spatie-translatable:^3.0 \
    pxlrbt/filament-activity-log:^3.0 \
    shuvroroy/filament-spatie-laravel-backup:^3.0 \
    shuvroroy/filament-spatie-laravel-health:^3.0

# Testing (development)
composer require --dev behat/behat:^3.29 \
    behat/gherkin:^4.16.1 \
    behat/mink:^1.12 \
    behat/mink-extension:^2.6 \
    behat/mink-goutte-driver:^1.4 \
    behat/mink-selenium2-driver:^1.6

# Publish configurations
php artisan vendor:publish --tag=tenancy-config
php artisan vendor:publish --provider="Spatie\Translatable\TranslatableServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\ModelStates\ModelStatesServiceProvider"
php artisan vendor:publish --provider="Spatie\ModelStatus\ModelStatusServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider"
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan vendor:publish --provider="Spatie\Health\HealthServiceProvider"
php artisan filament:install
php artisan shield:install

# Run migrations
php artisan migrate
```

## Verification

After installation, verify packages are installed correctly:

```bash
# Check installed packages
composer show | grep -E "(filament|spatie|stancl|cviebrock|tightenco)"

# Check PHP extensions
php -m | grep -E "(pdo_pgsql|intl)"

# Verify service providers
php artisan about
```

## Troubleshooting

### Common Issues

1. **Composer conflicts**: Use `composer update` to resolve dependency conflicts
2. **Missing extensions**: Install required PHP extensions (pdo_pgsql, intl)
3. **Permission errors**: Ensure storage and cache directories are writable
4. **Migration errors**: Check database connection and credentials

### Next Steps

- Review [Composer Configuration](030-composer-configuration.md) for complete `composer.json` example
- Proceed to [Database Setup](040-database-setup.md) to create the schema
