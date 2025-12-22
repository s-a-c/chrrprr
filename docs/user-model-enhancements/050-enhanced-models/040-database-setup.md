# Database Setup

This guide covers database migrations, schema design, and PostgreSQL optimizations for the enhanced User and Team models.

## Database Requirements

- **Database**: PostgreSQL 18
- **Character Set**: UTF-8
- **Extensions**: JSONB support (built-in), ltree (optional, for hierarchical queries)

## Migration Strategy

Migrations should be created in order:

1. Update users table (add ULID, context columns, state/status)
2. Create teams table (base table for STI)
3. Create supporting tables (translations, domains, pivot tables)
4. Create indexes and constraints

## Users Table Updates

### Migration: Add ULID and Enhanced Columns to Users

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ULID column for route binding
            $table->string('ulid', 26)->unique()->after('id');
            $table->index('ulid');

            // Translatable slug
            $table->json('slug')->nullable()->after('email');

            // Tenant relationship (Enterprise)
            $table->foreignId('tenant_id')->nullable()->after('slug')
                ->constrained('teams')->nullOnDelete();

            // State and status (will use enums)
            $table->string('state')->default('draft')->after('tenant_id');
            $table->string('status')->nullable()->after('state');

            // Context columns (current Organisation/Division/Department)
            $table->foreignId('current_organisation_id')->nullable()
                ->after('status')->constrained('teams')->nullOnDelete();
            $table->foreignId('current_division_id')->nullable()
                ->after('current_organisation_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('current_department_id')->nullable()
                ->after('current_division_id')->constrained('teams')->nullOnDelete();

            // Indexes for context columns
            $table->index('current_organisation_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['current_organisation_id']);
            $table->dropForeign(['current_division_id']);
            $table->dropForeign(['current_department_id']);

            $table->dropIndex(['ulid']);
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['current_organisation_id']);

            $table->dropColumn([
                'ulid',
                'slug',
                'tenant_id',
                'state',
                'status',
                'current_organisation_id',
                'current_division_id',
                'current_department_id',
            ]);
        });
    }
};
```

## Teams Table Creation

### Migration: Create Teams Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create enum types for PostgreSQL
        DB::statement("CREATE TYPE team_type AS ENUM ('enterprise', 'organisation', 'division', 'department', 'project')");
        DB::statement("CREATE TYPE team_state AS ENUM ('draft', 'active', 'inactive', 'archived')");
        DB::statement("CREATE TYPE team_status AS ENUM ('operational', 'under_review', 'merging', 'splitting')");

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->index('ulid');

            // STI type discriminator
            $table->string('type')->index(); // Will use enum type

            // Translatable fields (stored as JSONB for PostgreSQL performance)
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->jsonb('slug');

            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->index('parent_id');

            // Leadership
            $table->foreignId('executive_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('deputy_id')->nullable()->constrained('users')->nullOnDelete();

            // Tenant relationship (for Enterprise, this is self-referential)
            $table->foreignId('tenant_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->index('tenant_id');

            // State and status
            $table->string('state')->default('draft');
            $table->string('status')->nullable();

            // Timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['tenant_id', 'parent_id', 'type']);
            $table->index(['tenant_id', 'ulid']);
            $table->index(['executive_id', 'deputy_id']);

            // GIN indexes for JSONB columns (full-text search)
            $table->index(['name'], null, 'gin');
            $table->index(['slug'], null, 'gin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
        DB::statement('DROP TYPE IF EXISTS team_type');
        DB::statement('DROP TYPE IF EXISTS team_state');
        DB::statement('DROP TYPE IF EXISTS team_status');
    }
};
```

**Note**: For MySQL/SQLite compatibility, remove the PostgreSQL-specific enum types and use string columns instead.

## Supporting Tables

### Translation Tables (Optional)

If using separate translation tables instead of JSONB columns:

```php
<?php

Schema::create('team_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->string('locale', 10);
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('slug');

    $table->unique(['team_id', 'locale']);
    $table->index(['locale', 'slug']);
});

Schema::create('user_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('locale', 10);
    $table->string('name')->nullable();
    $table->text('description')->nullable();
    $table->string('slug')->nullable();

    $table->unique(['user_id', 'locale']);
    $table->index(['locale', 'slug']);
});
```

### Domains Table (Tenancy)

```php
<?php

Schema::create('domains', function (Blueprint $table) {
    $table->id();
    $table->foreignId('enterprise_id')->constrained('teams')->cascadeOnDelete();
    $table->string('domain')->unique();
    $table->boolean('is_primary')->default(false);
    $table->boolean('is_verified')->default(false);
    $table->string('verification_token')->nullable();
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();

    $table->index('enterprise_id');
    $table->index('domain');
});
```

### Pivot Tables

#### User-Enterprise (Many-to-Many)

```php
<?php

Schema::create('user_enterprise', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('enterprise_id')->constrained('teams')->cascadeOnDelete();
    $table->boolean('is_default')->default(false);
    $table->timestamps();

    $table->unique(['user_id', 'enterprise_id']);
    $table->index('user_id');
    $table->index('enterprise_id');
});
```

#### User-Organisation Access (Many-to-Many)

```php
<?php

Schema::create('user_organisation_access', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('organisation_id')->constrained('teams')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['user_id', 'organisation_id']);
    $table->index('user_id');
    $table->index('organisation_id');
});
```

## Index Strategy

### Composite Indexes

1. **Team Hierarchy Queries**: `(tenant_id, parent_id, type)`
   - Used for: Filtering teams by tenant, parent, and type
   - Example: `Team::where('tenant_id', $enterpriseId)->where('parent_id', $parentId)->where('type', 'division')`

2. **Route Lookups**: `(tenant_id, ulid)`
   - Used for: Fast route model binding with tenant scoping
   - Example: `Team::where('tenant_id', $enterpriseId)->where('ulid', $ulid)->first()`

3. **Leadership Queries**: `(executive_id, deputy_id)`
   - Used for: Finding teams by executive/deputy

### GIN Indexes (PostgreSQL JSONB)

- **name**: Full-text search on translatable names
- **slug**: Fast slug lookups across locales

### Unique Constraints

- `ulid` on users and teams (separate indexes)
- `(team_id, locale)` on translation tables (if used)
- `(user_id, enterprise_id)` on user_enterprise pivot
- `(user_id, organisation_id)` on user_organisation_access pivot

## Foreign Key Constraints

All foreign keys use appropriate actions:

- `cascadeOnDelete()`: For hierarchical relationships (parent-child)
- `restrictOnDelete()`: For mandatory relationships (executive)
- `nullOnDelete()`: For optional relationships (deputy, context columns)
- `cascadeOnDelete()`: For tenant relationships

## Data Types

### PostgreSQL-Specific

- **JSONB**: Used for translatable fields (name, description, slug)
  - Better query performance than JSON
  - Supports indexing and full-text search
- **ENUM types**: Used for state and status columns
  - Type safety at database level
  - Better performance than string columns

### Cross-Database Compatibility

For MySQL/SQLite compatibility:
- Use `json()` instead of `jsonb()`
- Use `string()` instead of enum types
- Validation handled at application level

## Migration Order

Create migrations in this order:

```bash
php artisan make:migration add_ulid_and_enhancements_to_users_table
php artisan make:migration create_teams_table
php artisan make:migration create_domains_table
php artisan make:migration create_user_enterprise_table
php artisan make:migration create_user_organisation_access_table
php artisan make:migration create_team_translations_table --optional
php artisan make:migration create_user_translations_table --optional
```

## Seeding Data

After migrations, create seeders:

```bash
php artisan make:seeder EnterpriseSeeder
php artisan make:seeder UserSeeder
php artisan make:seeder TeamHierarchySeeder
```

## Next Steps

- Review [Traits Implementation](050-traits-implementation.md) for trait code
- See [Models Implementation](070-models-implementation.md) for model definitions
- Check [Enums, States & Statuses](060-enums-states-statuses.md) for enum definitions
