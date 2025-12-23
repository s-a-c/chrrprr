# Data Model

**Feature**: Enhanced User and Team Models
**Date**: 2025-12-22
**Status**: Design Complete

## Overview

This document defines the complete data model for enhanced User and Team models with multi-tenancy, hierarchical teams, translatable attributes, and state management.

## Entities

### User

**Table**: `users`
**Primary Key**: `id` (integer), `ulid` (string, 26 chars, unique, for routes)
**Base Class**: `Illuminate\Foundation\Auth\User` (Authenticatable)

#### Attributes

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint | No | Primary key (for foreign keys) |
| `ulid` | string(26) | No | ULID for route binding (unique) |
| `name` | json | No | Translatable name (JSON: `{"en": "John Doe", "fr": "Jean Dupont"}`) |
| `email` | string | No | Email address (unique) |
| `slug` | json | Yes | Translatable slug (JSON: `{"en": "john-doe", "fr": "jean-dupont"}`) |
| `password` | string | No | Hashed password |
| `tenant_id` | bigint | Yes | Foreign key to `teams.id` (Enterprise) |
| `state` | string | No | User state enum (default: 'draft') |
| `status` | string | Yes | User status enum (nullable) |
| `current_organisation_id` | bigint | Yes | Foreign key to `teams.id` (current context) |
| `current_division_id` | bigint | Yes | Foreign key to `teams.id` (current context) |
| `current_department_id` | bigint | Yes | Foreign key to `teams.id` (current context) |
| `email_verified_at` | timestamp | Yes | Email verification timestamp |
| `two_factor_secret` | text | Yes | 2FA secret |
| `two_factor_recovery_codes` | text | Yes | 2FA recovery codes |
| `remember_token` | string(100) | Yes | Remember token |
| `created_at` | timestamp | No | Creation timestamp |
| `updated_at` | timestamp | No | Update timestamp |

#### Relationships

- `tenant()`: BelongsTo `Team` (Enterprise) via `tenant_id`
- `organisations()`: BelongsToMany `Team` (Organisation) via `user_organisation_access` pivot
- `currentOrganisation()`: BelongsTo `Team` (Organisation) via `current_organisation_id`
- `currentDivision()`: BelongsTo `Team` (Division) via `current_division_id`
- `currentDepartment()`: BelongsTo `Team` (Department) via `current_department_id`
- `enterprises()`: BelongsToMany `Team` (Enterprise) via `user_enterprise` pivot

#### Validation Rules

- Email must be unique
- ULID must be unique
- State must be valid enum value (Draft, Pending, Active, Suspended, Archived)
- Status must be valid enum value (Online, Offline, Away, Busy) if provided
- Tenant must be Enterprise type if provided
- Context IDs must be valid team types if provided

#### State Transitions

- Draft → Pending (automatic on approval)
- Pending → Active (automatic on approval)
- Active → Suspended (Enterprise Admin)
- Suspended → Active (Enterprise Admin)
- Active → Archived (Enterprise Admin)
- Suspended → Archived (Enterprise Admin)

### Team (Base Model - STI)

**Table**: `teams`
**Primary Key**: `id` (integer), `ulid` (string, 26 chars, unique, for routes)
**Base Class**: `Illuminate\Database\Eloquent\Model`
**STI Type**: Single Table Inheritance via `tightenco/parental`

#### Attributes

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint | No | Primary key (for foreign keys) |
| `ulid` | string(26) | No | ULID for route binding (unique) |
| `type` | string | No | STI discriminator: 'enterprise', 'organisation', 'division', 'department', 'project' |
| `name` | jsonb | No | Translatable name (JSONB for PostgreSQL) |
| `description` | jsonb | Yes | Translatable description (nullable) |
| `slug` | jsonb | No | Translatable hierarchical slug |
| `parent_id` | bigint | Yes | Foreign key to `teams.id` (null for Enterprise only) |
| `executive_id` | bigint | No | Foreign key to `users.id` (mandatory) |
| `deputy_id` | bigint | Yes | Foreign key to `users.id` (optional) |
| `tenant_id` | bigint | Yes | Foreign key to `teams.id` (Enterprise, self-referential for Enterprise) |
| `state` | string | No | Team state enum (default: 'draft') |
| `status` | string | Yes | Team status enum (nullable) |
| `deleted_at` | timestamp | Yes | Soft delete timestamp |
| `created_at` | timestamp | No | Creation timestamp |
| `updated_at` | timestamp | No | Update timestamp |

#### Relationships

- `parent()`: BelongsTo `Team` via `parent_id`
- `children()`: HasMany `Team` via `parent_id`
- `executive()`: BelongsTo `User` via `executive_id`
- `deputy()`: BelongsTo `User` via `deputy_id`
- `tenant()`: BelongsTo `Team` (Enterprise) via `tenant_id`
- `ancestors()`: HasMany (via adjacency list package)
- `descendants()`: HasMany (via adjacency list package)

#### Validation Rules

- ULID must be unique
- Type must be valid enum value
- Only Enterprise can have `parent_id = null`
- Organisation can only have Enterprise as parent
- Division can only have Organisation as parent
- Department can only have Division as parent
- Project can have any team type as parent
- Name must be unique within same parent AND same type within enterprise graph
- Executive and deputy cannot be the same person
- Executive and deputy must belong to same enterprise as team
- State must be valid enum value (Draft, Active, Inactive, Archived)
- Status must be valid enum value (Operational, UnderReview, Merging, Splitting) if provided

#### State Transitions

- Draft → Active (Enterprise Admin, Organisation Admin, Team Executive)
- Active → Inactive (Enterprise Admin, Organisation Admin, Team Executive)
- Inactive → Active (Enterprise Admin, Organisation Admin, Team Executive)
- Active → Archived (Enterprise Admin only)
- Inactive → Archived (Enterprise Admin only)

### Enterprise (Team Child)

**Type**: `enterprise`
**Parent**: None (root-level only)
**Children**: Organisation

#### Special Attributes

- Implements `TenantContract` from `stancl/tenancy`
- `tenant_id` is self-referential (points to self)
- Can have multiple domains via `domains()` relationship

#### Relationships

- `domains()`: HasMany `Domain`
- `organisations()`: HasMany `Team` where `type = 'organisation'` and `parent_id = this.id`

### Organisation (Team Child)

**Type**: `organisation`
**Parent**: Enterprise only
**Children**: Division

#### Relationships

- `enterprise()`: BelongsTo `Team` (Enterprise) via `parent_id`
- `divisions()`: HasMany `Team` where `type = 'division'` and `parent_id = this.id`

### Division (Team Child)

**Type**: `division`
**Parent**: Organisation only
**Children**: Department

#### Relationships

- `organisation()`: BelongsTo `Team` (Organisation) via `parent_id`
- `departments()`: HasMany `Team` where `type = 'department'` and `parent_id = this.id`

### Department (Team Child)

**Type**: `department`
**Parent**: Division only
**Children**: Project

#### Relationships

- `division()`: BelongsTo `Team` (Division) via `parent_id`
- `projects()`: HasMany `Team` where `type = 'project'` and `parent_id = this.id`

### Project (Team Child)

**Type**: `project`
**Parent**: Any team type
**Children**: Project (can nest)

#### Relationships

- `parent()`: BelongsTo `Team` (any type) via `parent_id`
- `projects()`: HasMany `Team` where `type = 'project'` and `parent_id = this.id`

### Domain

**Table**: `domains`
**Primary Key**: `id` (integer)

#### Attributes

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint | No | Primary key |
| `enterprise_id` | bigint | No | Foreign key to `teams.id` (Enterprise) |
| `domain` | string | No | Domain name (e.g., 'acme.example.com') |
| `is_primary` | boolean | No | Primary domain flag (default: false) |
| `is_verified` | boolean | No | Verification status (default: false) |
| `created_at` | timestamp | No | Creation timestamp |
| `updated_at` | timestamp | No | Update timestamp |

#### Relationships

- `enterprise()`: BelongsTo `Team` (Enterprise) via `enterprise_id`

#### Validation Rules

- Domain must be unique
- At least one primary domain per enterprise
- Domain format validation (subdomain pattern)

### User-Enterprise Pivot

**Table**: `user_enterprise`
**Primary Key**: Composite (`user_id`, `enterprise_id`)

#### Attributes

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `user_id` | bigint | No | Foreign key to `users.id` |
| `enterprise_id` | bigint | No | Foreign key to `teams.id` (Enterprise) |
| `is_default` | boolean | No | Default enterprise flag (default: false) |
| `created_at` | timestamp | No | Creation timestamp |
| `updated_at` | timestamp | No | Update timestamp |

#### Validation Rules

- One default enterprise per user
- User and enterprise must belong to same tenant context

### User-Organisation Access Pivot

**Table**: `user_organisation_access`
**Primary Key**: Composite (`user_id`, `organisation_id`)

#### Attributes

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `user_id` | bigint | No | Foreign key to `users.id` |
| `organisation_id` | bigint | No | Foreign key to `teams.id` (Organisation) |
| `created_at` | timestamp | No | Creation timestamp |
| `updated_at` | timestamp | No | Update timestamp |

#### Validation Rules

- User must belong to same enterprise as organisation
- Organisation must be type 'organisation'

## Indexes

### Users Table

- Primary: `id`
- Unique: `ulid`, `email`
- Index: `tenant_id`, `current_organisation_id`, `ulid`

### Teams Table

- Primary: `id`
- Unique: `ulid`
- Index: `type`, `parent_id`, `tenant_id`, `executive_id`, `deputy_id`
- Composite: `(tenant_id, parent_id, type)`, `(tenant_id, ulid)`, `(executive_id, deputy_id)`
- GIN (PostgreSQL): `name`, `slug` (for JSONB full-text search)

### Domains Table

- Primary: `id`
- Unique: `domain`
- Index: `enterprise_id`

### Pivot Tables

- Primary: `(user_id, enterprise_id)`, `(user_id, organisation_id)`
- Index: `user_id`, `enterprise_id`, `organisation_id`

## Constraints

### Foreign Key Constraints

- `users.tenant_id` → `teams.id` (nullOnDelete)
- `users.current_organisation_id` → `teams.id` (nullOnDelete)
- `users.current_division_id` → `teams.id` (nullOnDelete)
- `users.current_department_id` → `teams.id` (nullOnDelete)
- `teams.parent_id` → `teams.id` (cascadeOnDelete)
- `teams.executive_id` → `users.id` (restrictOnDelete)
- `teams.deputy_id` → `users.id` (nullOnDelete)
- `teams.tenant_id` → `teams.id` (cascadeOnDelete)
- `domains.enterprise_id` → `teams.id` (cascadeOnDelete)
- `user_enterprise.user_id` → `users.id` (cascadeOnDelete)
- `user_enterprise.enterprise_id` → `teams.id` (cascadeOnDelete)
- `user_organisation_access.user_id` → `users.id` (cascadeOnDelete)
- `user_organisation_access.organisation_id` → `teams.id` (cascadeOnDelete)

### Check Constraints

- `teams.type` IN ('enterprise', 'organisation', 'division', 'department', 'project')
- `teams.parent_id IS NULL` only when `type = 'enterprise'`
- `teams.executive_id != teams.deputy_id` (if deputy_id is not null)
- `users.state` IN ('draft', 'pending', 'active', 'suspended', 'archived')
- `users.status` IN ('online', 'offline', 'away', 'busy') (if not null)
- `teams.state` IN ('draft', 'active', 'inactive', 'archived')
- `teams.status` IN ('operational', 'under_review', 'merging', 'splitting') (if not null)

## Enums

### UserState

```php
enum UserState: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
```

### UserStatus

```php
enum UserStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Away = 'away';
    case Busy = 'busy';
}
```

### TeamType

```php
enum TeamType: string
{
    case Enterprise = 'enterprise';
    case Organisation = 'organisation';
    case Division = 'division';
    case Department = 'department';
    case Project = 'project';
}
```

### TeamState

```php
enum TeamState: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
```

### TeamStatus

```php
enum TeamStatus: string
{
    case Operational = 'operational';
    case UnderReview = 'under_review';
    case Merging = 'merging';
    case Splitting = 'splitting';
}
```

## Data Integrity Rules

1. **Hierarchy Constraints**:
   - Only Enterprise can be root-level (`parent_id = null`)
   - Organisation can only have Enterprise as parent
   - Division can only have Organisation as parent
   - Department can only have Division as parent
   - Project can have any team type as parent

2. **Uniqueness Constraints**:
   - Team names must be unique within same parent AND same type within enterprise graph
   - ULIDs must be unique across all tables
   - Email must be unique across all users
   - Domain must be unique across all domains

3. **Leadership Constraints**:
   - Executive is mandatory for all teams
   - Deputy is optional
   - Executive and deputy cannot be the same person
   - Executive and deputy must belong to same enterprise as team

4. **Tenant Constraints**:
   - All teams must belong to an Enterprise (tenant)
   - Users must belong to at least one Enterprise
   - Cross-tenant relationships are not allowed

5. **Context Constraints**:
   - User's current context (organisation/division/department) must be valid team types
   - Context must belong to user's accessible organisations
   - Context must belong to user's current enterprise

## Migration Strategy

1. Add ULID and enhanced columns to existing `users` table
2. Create `teams` table with STI structure
3. Create supporting tables (`domains`, pivot tables)
4. Create indexes and constraints
5. Migrate existing data (generate ULIDs, set default states)
6. Maintain backward compatibility with integer ID routes/APIs
