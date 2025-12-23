# Composer Configuration

This document provides a complete `composer.json` configuration with all required packages organized by category.

## Complete composer.json Structure

```json
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/livewire-starter-kit",
    "type": "project",
    "description": "Laravel application with enhanced User and Team models",
    "license": "MIT",
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
        },
        "fluxui-pro": {
            "type": "composer",
            "url": "https://composer.fluxui.dev"
        },
        "laravel-comments": {
            "type": "composer",
            "url": "https://satis.spatie.be"
        },
        "laravel-labs-starter-kit-browser-tests": {
            "type": "vcs",
            "url": "https://github.com/laravel-labs/starter-kit-browser-tests"
        },
        "psalm-plugin-laravel": {
            "type": "vcs",
            "url": "https://github.com/s-a-c/psalm-plugin-laravel"
        }
    },
    "require": {
        "php": "^8.5",

        "laravel/folio": "dev-master",
        "laravel/fortify": "^1.33",
        "laravel/framework": "^12.43.1",
        "laravel/tinker": "^2.10.2",
        "livewire/flux": "^2.10.2",
        "livewire/livewire": "^4.0@beta",

        "filament/filament": "^5.0",
        "stancl/tenancy": "^4.14",

        "cviebrock/eloquent-sluggable": "^10.0",
        "spatie/laravel-translatable": "^6.4",
        "spatie/laravel-translation-loader": "^3.6",
        "staudenmeir/laravel-adjacency-list": "^10.0",
        "tightenco/parental": "^2.9",
        "symfony/uid": "^7.2",

        "spatie/laravel-model-states": "^2.6",
        "spatie/laravel-model-status": "^2.3",
        "spatie/laravel-data": "^4.9",

        "spatie/laravel-permission": "^6.11",
        "ukeloop/laravel-impersonatable-guard": "^2.0",

        "laravel-auditing/auditing": "^16.0",
        "binafy/laravel-user-monitoring": "^2.0",
        "spatie/laravel-activitylog": "^4.8",

        "spatie/laravel-tags": "^5.0",
        "spatie/laravel-medialibrary": "^11.0",
        "spatie/laravel-settings": "^3.4",
        "spatie/laravel-backup": "^8.17",
        "spatie/laravel-health": "^1.38",
        "spatie/laravel-sitemap": "^7.1",
        "lakshan-madushanka/commenter": "^1.0",

        "laravel/horizon": "^6.0",
        "laravel/pennant": "^1.18",
        "laravel/pulse": "^1.24",
        "laravel/socialite": "^5.18",
        "laravel/telescope": "^5.22",

        "bezhansalleh/filament-shield": "dev-main",
        "filament/spatie-laravel-media-library-plugin": "^4.0",
        "filament/spatie-laravel-settings-plugin": "^4.0",
        "lara-zeus/spatie-translatable": "^3.0",
        "pxlrbt/filament-activity-log": "^3.0",
        "shuvroroy/filament-spatie-laravel-backup": "^3.0",
        "shuvroroy/filament-spatie-laravel-health": "^3.0"
    },
    "require-dev": {
        "barryvdh/laravel-debugbar": "^3.16.2",
        "barryvdh/laravel-ide-helper": "^3.6.1",
        "behat/behat": "^3.29",
        "behat/gherkin": "^4.16.1",
        "behat/mink": "^1.12",
        "behat/mink-extension": "^2.6",
        "behat/mink-goutte-driver": "^1.4",
        "behat/mink-selenium2-driver": "^1.6",
        "driftingly/rector-laravel": "^2.1.8",
        "ergebnis/composer-normalize": "^2.48.2",
        "fakerphp/faker": "^1.24.1",
        "infection/infection": "^0.31.9",
        "jasonmccreary/laravel-test-assertions": "^2.8",
        "larastan/larastan": "^3.8.1",
        "laravel-labs/starter-kit-browser-tests": "dev-main",
        "laravel-shift/blueprint": "^2.13",
        "laravel/boost": "^1.8.7",
        "laravel/pail": "^1.2.4",
        "laravel/pint": "^1.26",
        "laravel/sail": "^1.51",
        "mockery/mockery": "^1.6.12",
        "nunomaduro/collision": "^8.8.3",
        "pestphp/pest": "^4.2.0",
        "pestphp/pest-plugin-arch": "^4.0",
        "pestphp/pest-plugin-browser": "^4.1.1",
        "pestphp/pest-plugin-faker": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "pestphp/pest-plugin-profanity": "^4.2.1",
        "pestphp/pest-plugin-type-coverage": "^4.0.3",
        "phpmd/phpmd": "^2.15",
        "phpstan/extension-installer": "^1.4.3",
        "phpstan/phpstan": "^2.1.33",
        "phpstan/phpstan-deprecation-rules": "^2.0.3",
        "phpstan/phpstan-phpunit": "^2.0.11",
        "phpstan/phpstan-strict-rules": "^2.0.7",
        "phpunit/phpunit": "^12.5.3",
        "psalm/plugin-laravel": "dev-master",
        "psalm/plugin-mockery": "dev-master",
        "psalm/plugin-phpunit": "dev-master",
        "rector/rector": "^2.2.14",
        "rector/type-perfect": "^2.1.1",
        "roave/security-advisories": "dev-latest",
        "soloterm/solo": "^0.5",
        "spatie/laravel-login-link": "^1.6.3",
        "spatie/laravel-missing-page-redirector": "^2.11.1",
        "spatie/laravel-queueable-action": "^2.16.2",
        "spatie/laravel-ray": "^1.43.2",
        "spatie/laravel-web-tinker": "^1.10.1",
        "spatie/pest-plugin-snapshots": "^2.2.1",
        "vimeo/psalm": "^7.0@dev"
    }
}
```

## Package Categories

### Core Framework (Already Installed)
- `laravel/folio` - File-based routing
- `laravel/fortify` - Authentication
- `laravel/framework` - Core framework
- `livewire/livewire` - Livewire framework
- `livewire/flux` - Flux UI components

### Admin Panel & Multi-Tenancy
- `filament/filament` - Admin panel
- `stancl/tenancy` - Multi-tenancy support

### Model Enhancement
- `cviebrock/eloquent-sluggable` - Slug generation
- `spatie/laravel-translatable` - Translatable content
- `spatie/laravel-translation-loader` - Translation loading
- `staudenmeir/laravel-adjacency-list` - Hierarchical relationships
- `tightenco/parental` - Single Table Inheritance (STI)
- `symfony/uid` - ULID generation

### State & Status Management
- `spatie/laravel-model-states` - State machine
- `spatie/laravel-model-status` - Status tracking
- `spatie/laravel-data` - Data Transfer Objects

### Permissions & Security
- `spatie/laravel-permission` - Roles & permissions
- `ukeloop/laravel-impersonatable-guard` - User impersonation

### Auditing & Monitoring
- `laravel-auditing/auditing` - Model auditing
- `binafy/laravel-user-monitoring` - User activity monitoring
- `spatie/laravel-activitylog` - Activity logging

### Additional Features
- `spatie/laravel-tags` - Tagging system
- `spatie/laravel-medialibrary` - Media management
- `spatie/laravel-settings` - Settings management
- `spatie/laravel-backup` - Backup management
- `spatie/laravel-health` - Health checks
- `spatie/laravel-sitemap` - Sitemap generation
- `lakshan-madushanka/commenter` - Comment system

### Laravel Ecosystem
- `laravel/horizon` - Queue monitoring
- `laravel/pennant` - Feature flags
- `laravel/pulse` - Application monitoring
- `laravel/socialite` - Social authentication
- `laravel/telescope` - Debugging tool

### Filament Plugins
- `bezhansalleh/filament-shield` - Role & permission management
- `filament/spatie-laravel-media-library-plugin` - Media library integration
- `filament/spatie-laravel-settings-plugin` - Settings management
- `lara-zeus/spatie-translatable` - Translatable fields
- `pxlrbt/filament-activity-log` - Activity log viewer
- `shuvroroy/filament-spatie-laravel-backup` - Backup management
- `shuvroroy/filament-spatie-laravel-health` - Health checks

### Testing & Quality (Dev Dependencies)
- `behat/behat` - BDD testing
- `behat/mink` - Browser emulation
- `pestphp/pest` - Testing framework
- `phpstan/phpstan` - Static analysis
- `larastan/larastan` - Laravel static analysis
- `vimeo/psalm` - Type checking
- `phpmd/phpmd` - Code quality
- `rector/rector` - Refactoring

## Installation

After updating `composer.json`, run:

```bash
composer install
```

Or if updating existing installation:

```bash
composer update
```

## Version Constraints

All version constraints follow semantic versioning:
- `^` allows updates that don't break backward compatibility
- Specific versions pinned where necessary for compatibility
- Development branches (`dev-main`) used for custom forks

## Repository Notes

Some packages require custom repositories:
- `filament-shield`: Custom VCS repository (fork)
- `filament-spatie-laravel-media-library-plugin`: Filament Composer repository
- `filament-spatie-laravel-settings-plugin`: Filament Composer repository
- `fluxui-pro`: Private Composer repository
- `laravel-comments`: Spatie Satis repository
- `psalm-plugin-laravel`: Custom VCS repository (fork)

## Next Steps

- See [Package Installation Guide](020-package-installation.md) for installation steps
- Proceed to [Database Setup](040-database-setup.md) after installation
