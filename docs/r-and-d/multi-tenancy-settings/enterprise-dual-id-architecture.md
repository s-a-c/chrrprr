# Enterprise Dual-ID Architecture

## Overview

The application uses a **dual-ID architecture** for the `Enterprise` model (tenant), which serves as the root of the multi-tenant hierarchy. This design provides:

- **Integer IDs** for efficient internal relationships
- **ULIDs** for external identification and Stancl Tenancy integration

## The Two ID Systems

### 1. Integer `id` (Primary Key)

| Aspect | Value |
| ------ | ----- |
| Column | `teams.id` |
| Type | `bigint` (autoincrement) |
| Use Case | Internal Eloquent relationships |
| Generator | Database autoincrement |

Used in:

- `parent_id` foreign keys (team hierarchy)
- `tenant_id` foreign keys (team → enterprise relationship)
- Eloquent `belongsTo` / `hasMany` relations
- Permission system team scoping

### 2. String `ulid` (Tenant Key)

| Aspect | Value |
| ------ | ----- |
| Column | `teams.ulid` |
| Type | `string` (26 characters) |
| Use Case | Stancl Tenancy, routing, external APIs |
| Generator | `HasUlid` trait |

Used in:

- Stancl Tenancy tenant identification (`TenantContract`)
- `tenants` table primary key (for Stancl's tenant registry)
- `domains.tenant_id` foreign key (string, references `tenants.id`)
- Route model binding (`/enterprise/{ulid}`)

## Configuration

### `config/tenancy.php`

```php
'id_generator' => null, // Database autoincrement handles integer IDs
'tenant_model' => Enterprise::class,

```

- `id_generator => null` disables Stancl's built-in ID generation
- ULID generation is handled by the `HasUlid` trait, not Stancl

### Enterprise Model Implementation

```php
class Enterprise extends Team implements TenantContract
{
    use HasUlid;

    public function getTenantKeyName(): string
    {
        return 'ulid'; // Stancl uses this, not 'id'
    }

    public function getTenantKey(): string
    {
        return $this->ulid;
    }
}

```

## Database Schema

```log
┌─────────────────────────────────────┐
│ teams (single table inheritance)    │
├─────────────────────────────────────┤
│ id        bigint PK (autoincrement) │
│ ulid      varchar(26) UNIQUE        │
│ parent_id bigint FK → teams.id      │
│ tenant_id bigint FK → teams.id      │
│ type      varchar                   │
│ ...                                 │
│ created_at, updated_at              │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ tenants (Stancl registry)           │
├─────────────────────────────────────┤
│ id        varchar PK (= ulid)       │
│ created_at, updated_at              │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ domains                             │
├─────────────────────────────────────┤
│ id        bigint PK                 │
│ domain    varchar UNIQUE            │
│ tenant_id varchar FK → tenants.id   │
└─────────────────────────────────────┘

```

## Key Points

1. **Seeders** must insert into both `teams` (with integer relationships) and `tenants` (with ULID as primary key)

2. **Domains** use the ULID (string) as `tenant_id`, not the integer ID

3. **Team relationships** (`parent_id`, `tenant_id` on teams) use the integer ID for performance

4. **Stancl Tenancy** identifies tenants by ULID via `getTenantKey()`

## Common Pitfall

When creating domains programmatically:

```php
// ❌ Wrong - uses integer ID
$domain->tenant_id = $enterprise->id;

// ✅ Correct - uses ULID
$domain->tenant_id = $enterprise->ulid;

```
