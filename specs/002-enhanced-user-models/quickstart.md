# Quick Start Guide

**Feature**: Enhanced User and Team Models
**Date**: 2025-12-22

## Overview

This guide provides a quick start for implementing the enhanced User and Team models feature. Follow these steps to get up and running.

## Prerequisites

- Laravel 12 application
- PHP 8.5.1+
- PostgreSQL 18 (production) or SQLite (development)
- Composer installed
- Node.js and npm/yarn (for frontend assets)

## Step 1: Install Dependencies

Install all required Composer packages:

```bash
# Core framework packages
composer require filament/filament:^5.0
composer require stancl/tenancy:^4.14

# ULID support
composer require symfony/uid:^7.2

# Slug generation and translatable content
composer require cviebrock/eloquent-sluggable:^10.0
composer require spatie/laravel-translatable:^6.4
composer require spatie/laravel-translation-loader:^3.6

# Hierarchical relationships and STI
composer require staudenmeir/laravel-adjacency-list:^10.0
composer require tightenco/parental:^2.9

# State & status management
composer require spatie/laravel-model-states:^2.6
composer require spatie/laravel-model-status:^2.3

# Data & validation
composer require spatie/laravel-data:^4.9

# Permissions & security
composer require spatie/laravel-permission:^6.11
```

See [Package Installation Guide](../../docs/user-model-enhancements/050-enhanced-models/020-package-installation.md) for complete list.

## Step 2: Configure Multi-Tenancy

### 2.1 Publish Tenancy Configuration

```bash
php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider"
```

### 2.2 Configure Bootstrap

Update `bootstrap/app.php` to include tenancy middleware:

```php
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('tenant', [
            InitializeTenancyBySubdomain::class,
            PreventAccessFromCentralDomains::class,
        ]);
    })
    // ... rest of configuration
    ->create();
```

### 2.3 Configure Domain Routing

Set up wildcard DNS for local development:
- Add `*.test` to `/etc/hosts` (or use Laravel Herd which handles this automatically)
- Configure your domain provider for production subdomains

## Step 3: Run Migrations

### 3.1 Create Migrations

```bash
# Add ULID and enhanced columns to users
php artisan make:migration add_ulid_to_users_table

# Create teams table
php artisan make:migration create_teams_table

# Create domains table
php artisan make:migration create_domains_table

# Create pivot tables
php artisan make:migration create_user_enterprise_table
php artisan make:migration create_user_organisation_access_table
```

### 3.2 Implement Migrations

See [Database Setup](../../docs/user-model-enhancements/050-enhanced-models/040-database-setup.md) for complete migration code.

### 3.3 Run Migrations

```bash
php artisan migrate
```

## Step 4: Create Traits

Create reusable traits in `app/Models/Concerns/`:

- `HasUlid.php` - ULID generation and route binding
- `HasTranslatableAttributes.php` - Translatable attribute handling
- `HasTranslatableSlug.php` - Translatable slug generation

See [Traits Implementation](../../docs/user-model-enhancements/050-enhanced-models/050-traits-implementation.md) for complete code.

## Step 5: Create Enums

Create PHP 8.1+ backed enums in `app/Enums/`:

- `UserState.php` - User state enum
- `UserStatus.php` - User status enum
- `TeamType.php` - Team type enum
- `TeamState.php` - Team state enum
- `TeamStatus.php` - Team status enum

See [Enums Implementation](../../docs/user-model-enhancements/050-enhanced-models/060-enums-states-statuses.md) for complete code.

## Step 6: Create Models

### 6.1 Update User Model

Update `app/Models/User.php` to include:
- ULID trait
- Translatable attributes trait
- Translatable slug trait
- BelongsToTenant trait
- State and status management
- Context relationships

### 6.2 Create Team Models

Create team models in `app/Models/`:

- `Team.php` - Base STI model
- `Enterprise.php` - Enterprise model (implements TenantContract)
- `Organisation.php` - Organisation model
- `Division.php` - Division model
- `Department.php` - Department model
- `Project.php` - Project model

See [Models Implementation](../../docs/user-model-enhancements/050-enhanced-models/070-models-implementation.md) for complete code.

## Step 7: Create Form Requests

Create validation form requests in `app/Http/Requests/`:

```bash
php artisan make:request StoreTeamRequest
php artisan make:request UpdateTeamRequest
php artisan make:request SwitchContextRequest
```

Implement validation rules based on [Business Rules](../../docs/user-model-enhancements/050-enhanced-models/015-business-rules.md).

## Step 8: Create Livewire Components

Create Livewire components for UI:

```bash
php artisan make:livewire Teams/CreateTeam
php artisan make:livewire Teams/EditTeam
php artisan make:livewire Teams/TeamList
php artisan make:livewire Context/SwitchContext
```

Use Flux UI components for consistent styling.

## Step 9: Write Tests

### 9.1 Feature Tests

Create feature tests in `tests/Feature/`:

```bash
php artisan make:test --pest Teams/TeamCreationTest
php artisan make:test --pest Teams/TeamHierarchyTest
php artisan make:test --pest Context/ContextSwitchingTest
php artisan make:test --pest Tenancy/TenantIsolationTest
```

### 9.2 Unit Tests

Create unit tests in `tests/Unit/`:

```bash
php artisan make:test --pest --unit Models/UserTest
php artisan make:test --pest --unit Models/TeamTest
php artisan make:test --pest --unit Enums/UserStateTest
```

### 9.3 Run Tests

```bash
php artisan test
```

Ensure 99% PHP test coverage and 100% type coverage.

## Step 10: Data Migration

### 10.1 Generate ULIDs for Existing Users

Create a migration or command to generate ULIDs:

```bash
php artisan make:command GenerateUserUlids
```

### 10.2 Set Default States

Update existing users to have appropriate default states:
- Active users → `state = 'active'`
- New users → `state = 'draft'`

### 10.3 Initialize Context

Set default context for users based on their current organisation access.

## Step 11: Configure Filament (Optional)

If using Filament for admin panel:

```bash
php artisan filament:install --panels
php artisan make:filament-resource Team
php artisan make:filament-resource User
```

Configure resources to use ULID for route binding and display translatable attributes.

## Step 12: Deploy

### 12.1 Pre-Deployment Checklist

- [ ] All tests passing
- [ ] Code formatted (Pint)
- [ ] Type analysis passing (PHPStan level 9)
- [ ] Architecture checks passing (Mago)
- [ ] Database migrations tested
- [ ] Data migration scripts tested
- [ ] Backward compatibility verified

### 12.2 Deployment Steps

1. Run migrations on production
2. Run data migration scripts
3. Deploy code
4. Verify tenant identification works
5. Test context switching
6. Monitor for errors

## Common Issues

### Issue: ULID Generation Fails

**Solution**: Ensure `symfony/uid:^7.2` is installed and PHP 8.2+ is used.

### Issue: Tenant Not Identified

**Solution**: Verify subdomain routing is configured correctly and middleware is applied.

### Issue: Context Scoping Too Restrictive

**Solution**: Use `withoutContextScope()` method for privileged users or adjust permissions.

### Issue: State Transition Fails

**Solution**: Verify state machine configuration and user permissions for the transition.

## Next Steps

- Review [Business Rules](../../docs/user-model-enhancements/050-enhanced-models/015-business-rules.md) for detailed validation requirements
- Review [Models Implementation](../../docs/user-model-enhancements/050-enhanced-models/070-models-implementation.md) for complete model code
- Review [API Contracts](./contracts/openapi.yaml) for API endpoint specifications
- Review [Data Model](./data-model.md) for complete database schema

## Support

For detailed implementation guidance, refer to:
- [Overview & Architecture](../../docs/user-model-enhancements/050-enhanced-models/010-overview.md)
- [Package Installation](../../docs/user-model-enhancements/050-enhanced-models/020-package-installation.md)
- [Database Setup](../../docs/user-model-enhancements/050-enhanced-models/040-database-setup.md)
- [Traits Implementation](../../docs/user-model-enhancements/050-enhanced-models/050-traits-implementation.md)
- [Enums Implementation](../../docs/user-model-enhancements/050-enhanced-models/060-enums-states-statuses.md)
- [Models Implementation](../../docs/user-model-enhancements/050-enhanced-models/070-models-implementation.md)
