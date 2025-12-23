# Overview & Architecture

## System Overview

This document consolidates and refactors the Team model implementation, adding comprehensive step-by-step installation guides, complete package configuration, and enhanced architecture for a SaaS platform with enterprise-based tenancy and fine-grained organizational scoping.

## Architecture Decisions

### Tenancy Model

**Enterprise = Tenant**: Enterprise implements `TenantContract`, subdomain maps to Enterprise

- Subdomain pattern: `{enterprise-slug}.example.com` → Enterprise
- Fine-grained scoping: Application-level context for Organisation/Division/Department
- Context switching: Users can switch between Organisations within their Enterprise
- Data isolation: Automatic scoping via `BelongsToTenant` trait from `stancl/tenancy`

### Primary Key Strategy

**Dual Keys**: Integer `id` for foreign keys/performance, ULID `ulid` for routes/public APIs

- Route model binding uses ULID via `getRouteKeyName()`
- Foreign keys continue using integer `id` for performance
- ULID generation: Uses Laravel's `Str::ulid()` method (requires `symfony/uid:^7.2`)

### Slug Strategy

**Translatable Hierarchical Slugs**: Slugs support multiple languages and reflect hierarchy

- Implementation: `cviebrock/eloquent-sluggable` + `spatie/laravel-translatable`
- Pattern: `{parent-slug}/{current-slug}` (e.g., `acme-corp/sales-team`)
- Unique per graph and locale
- Slug column is translatable (JSON), generated per locale from translatable name attribute
- Automatic regeneration when parent changes

### Model Architecture

**Trait-Based Composition**: All shared functionality implemented as reusable traits

- Location: `app/Models/Concerns/`
- Traits: `HasUlid`, `HasTranslatableAttributes`, `HasTranslatableSlug`
- No base model: Models extend appropriate base classes (User extends Authenticatable, Team extends Model)
- Single responsibility: Each trait has one clear purpose
- Laravel conventions: Uses `boot{TraitName}()` methods
- Flexible composition: Models can mix and match traits as needed

## Technology Stack

### Core Framework
- Laravel 12
- PHP 8.5+
- PostgreSQL 18
- Livewire 4
- Filament 5

### Key Packages
- `stancl/tenancy` - Multi-tenancy
- `spatie/laravel-translatable` - Translatable content
- `cviebrock/eloquent-sluggable` - Slug generation
- `spatie/laravel-model-states` - State machines
- `spatie/laravel-model-status` - Status tracking
- `tightenco/parental` - Single Table Inheritance (STI)
- `staudenmeir/laravel-adjacency-list` - Hierarchical relationships

### Testing Stack
- Pest 4 (with Playwright integration)
- Behat (BDD testing)
- PHPStan level 9
- Psalm
- PHPMD
- Vitest (JavaScript/TypeScript)

## Team Hierarchy Structure

```
Enterprise (Root, Tenant)
  └── Organisation
       └── Division
            └── Department
                 └── Project (can be child of any type)
```

**Key Constraints:**
- Only Enterprise can be root-level (`parent_id = null`)
- Project must always have a parent (cannot be root-level)
- Strict parent-child type validation enforced
- Maximum depth: 10 levels (recommended)

## User Model Enhancements

### New Features
- ULID primary key for routes
- Translatable attributes (name, description, slug)
- Tenant relationship (belongs to Enterprise)
- State machine (Draft → Pending → Active → Suspended → Archived)
- Status tracking (Online, Offline, Away, Busy)
- Context scoping (current Organisation/Division/Department)

### Relationships
- `tenant()` - Belongs to Enterprise (via BelongsToTenant)
- `organisations()` - Many-to-many with Organisation teams
- `currentOrganisation()` - Accessor for session context

## Team Model Hierarchy

### Base Team Model
- Extends `Illuminate\Database\Eloquent\Model`
- Uses traits: HasUlid, HasTranslatableAttributes, HasTranslatableSlug, BelongsToTenant, etc.
- Single Table Inheritance (STI) via `tightenco/parental`

### Child Models
- **Enterprise**: Implements `TenantContract`, uses `HasChildren`
- **Organisation, Division, Department, Project**: Use `HasParent`
- All child models compose same traits as base Team model

### Business Logic
- Hierarchy validation
- Unique name per graph validation
- Hierarchical slug generation (translatable per locale)
- Executive/Deputy constraint validation
- Graph root identification methods

## Context Scoping

### Storage Strategy
- Database: Store `current_organisation_id`, `current_division_id`, `current_department_id` in `users` table
- Session: Cache context in session for performance
- Persistence: Context persists across sessions/logouts

### Scoping Behavior
- Local scope: `scopeInContext()` method (not global scope)
- Affected models: Organisation, Division, Department, Project
- Not affected: Enterprise (tenant-level), User (user-level)
- Bypass: `withoutContextScope()` method (requires permission)

## State & Status Management

### User States (Backed Enum)
- Draft → Pending → Active → Suspended → Archived
- Enhanced with Filament colors for UI display

### User Statuses (Backed Enum)
- Online, Offline, Away, Busy
- Real-time presence tracking

### Team States (Backed Enum)
- Draft → Active → Inactive → Archived

### Team Statuses (Backed Enum)
- Operational, UnderReview, Merging, Splitting

All states/statuses use native PHP 8.1+ enums, enhanced with Filament badge colors.

## Next Steps

1. Review [Business Rules & Validation](015-business-rules.md) for detailed requirements
2. Follow [Package Installation Guide](020-package-installation.md) to install dependencies
3. Set up database schema using [Database Setup](040-database-setup.md)
4. Implement traits following [Traits Implementation](050-traits-implementation.md)
5. Create models using [Models Implementation](070-models-implementation.md)
