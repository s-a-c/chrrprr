# Laravel 12: Hybrid PostgreSQL Schema Architecture (App & Search)

---

## Executive Summary

This guide details how to isolate core application data (schema: `chrrprr`) from heavy search indexes (schema: `search`) while maintaining high-performance Full-Text Search (FTS), Fuzzy Matching, and strict architectural guardrails. This guide also provides a comparative analysis of using GIN vs RUM for weighted full-text search.

---

## 1. Environment & Database Configuration

We must define the custom schemas and ensure the application connection string prioritizes them.

**File:** `.env`

```env
APP_ID=chrrprr
DB_SCHEMA=chrrprr
DB_SEARCH_SCHEMA=search
SCOUT_DRIVER=database

```

**File:** `config/database.php`
Ensure the `pgsql` connection reads these schemas and sets the `search_path` to include them. The `dump` configuration is crucial for schema dumps to work correctly during testing.

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'laravel'),
    // ...
    'schema' => env('DB_SCHEMA', 'public'),
    'search_schema' => env('DB_SEARCH_SCHEMA', 'search'),

    // Priority: App Data (chrrprr) -> Search Data (search) -> Extensions (public)
    'search_path' => implode(',', [
        env('DB_SCHEMA', 'public'),
        env('DB_SEARCH_SCHEMA', 'search'),
        'public'
    ]),

    // Ensure pg_dump includes both schemas
    'dump' => [
        'add_extra_args' => '--schema=' . env('DB_SCHEMA') . ' --schema=' . env('DB_SEARCH_SCHEMA') . ' --no-owner',
    ],
],

```

---

## 2. Infrastructure Initialization

The `Team` model relies on translatable attributes (JSON). We need the `pg_trgm` (trigram) extension for fuzzy matching and `unaccent` for improved text search.

**File:** `database/migrations/0000_01_01_000000_initialize_app_infrastructure.php`

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

return new class extends Migration {
    public function up(): void {
        if (DB::getDriverName() !== 'pgsql') return;

        $appSchema = Config::get('database.connections.pgsql.schema');
        $searchSchema = Config::get('database.connections.pgsql.search_schema');

        // 1. Extensions in 'public' so they are globally accessible
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm SCHEMA public');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent SCHEMA public');

        // 2. Create physical schemas
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$appSchema}\"");
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$searchSchema}\"");

        // 3. Set session path to ensure subsequent migrations find these schemas
        DB::statement("SET search_path TO \"{$appSchema}\", \"{$searchSchema}\", public");
    }

    public function down(): void {
        if (DB::getDriverName() !== 'pgsql') return;
        $appSchema = Config::get('database.connections.pgsql.schema');
        $searchSchema = Config::get('database.connections.pgsql.search_schema');

        if ($appSchema && $appSchema !== 'public') {
            DB::statement("DROP SCHEMA IF EXISTS \"{$appSchema}\" CASCADE");
        }
        if ($searchSchema && $searchSchema !== 'search') {
            DB::statement("DROP SCHEMA IF EXISTS \"{$searchSchema}\" CASCADE");
        }
    }
};

```

---

## 3. Core Architecture: Traits & Contracts

Models like `User` and `Team` must dynamically resolve their table names based on the configured schema. However, external models like `Domain` (from `Stancl\Tenancy`) or purely search-based models need exemption.

**File:** `app/Contracts/SchemaScopedModel.php`

```php
namespace App\Contracts;

interface SchemaScopedModel
{
    /**
     * Get the table associated with the model, scoped to the app schema.
     */
    public function getTable();
}

```

**File:** `app/Models/Concerns/HasCustomSchema.php`

```php
namespace App\Models\Concerns;

use Illuminate\Support\Facades\Config;

trait HasCustomSchema
{
    public function getTable(): string
    {
        $table = parent::getTable();
        $connection = $this->getConnectionName() ?? Config::get('database.default');
        $driver = Config::get("database.connections.{$connection}.driver");

        // Only alter table name for PostgreSQL when a custom schema is defined
        // and the table doesn't already contain a schema prefix (e.g. 'search.logs')
        $schema = Config::get('database.connections.pgsql.schema');

        if ($driver === 'pgsql' && $schema && $schema !== 'public' && !str_contains($table, '.')) {
            return "{$schema}.{$table}";
        }

        return $table;
    }
}

```

**Implementation Step:** Apply this trait and interface to `User.php` and `Team.php`.

---

## 4. Search Implementation (Weighted & Fuzzy)

Since `Team` uses `HasTranslatableAttributes` (JSON columns), our search implementation must extract the correct language values from the JSON structure before indexing.

### 4.1 Migration: Generated Columns & Indexes

We use PostgreSQL **Generated Columns** (`STORED`) to create a `tsvector` column that auto-updates whenever the `name` or `bio` changes.

**File:** `database/migrations/2026_01_01_000001_add_hybrid_search_to_teams.php`

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        $schema = config('database.connections.pgsql.schema');

        Schema::table('teams', function (Blueprint $table) use ($schema) {
            // 1. Weighted Full-Text Search Vector (A = Name, B = Bio)
            // We extract the 'en' key from the JSON columns.
            DB::statement("
                ALTER TABLE \"{$schema}\".teams
                ADD COLUMN search_vector tsvector
                GENERATED ALWAYS AS (
                    setweight(to_tsvector('english', unaccent(coalesce(name->>'en', ''))), 'A') ||
                    setweight(to_tsvector('english', unaccent(coalesce(bio->>'en', ''))), 'B')
                ) STORED
            ");

            // 2. GIN Index for blazing fast Full-Text Search
            DB::statement("CREATE INDEX teams_fts_idx ON \"{$schema}\".teams USING GIN(search_vector)");

            // 3. Trigram Index for Fuzzy Matching (Typo tolerance) on the name
            DB::statement("CREATE INDEX teams_fuzzy_idx ON \"{$schema}\".teams USING GIST ((name->>'en') gist_trgm_ops)");
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });
    }
};

```

### 4.2 Model Update: Team.php

We add the scope for fuzzy matching and standard Scout configuration.

```php
// In App/Models/Team.php

use App\Contracts\SchemaScopedModel;
use App\Models\Concerns\HasCustomSchema;
use Laravel\Scout\Searchable;

class Team extends Model implements SchemaScopedModel
{
    use HasCustomSchema;
    use Searchable;
    // ... existing traits (HasTranslatableAttributes, etc.)

    /**
     * Typo-tolerant fuzzy search scope using pg_trgm.
     */
    public function scopeFuzzySearch($query, string $term)
    {
        // Extract English name from JSON for comparison
        return $query->whereRaw("(name->>'en') % ?", [$term])
                     ->orderByRaw("similarity((name->>'en'), ?) DESC", [$term]);
    }

    /**
     * Scout: Define the indexable data array.
     */
    public function toSearchableArray(): array
    {
        // Scout usage if using 'database' driver with WHERE clauses
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', 'en'),
            'bio' => $this->getTranslation('bio', 'en'),
            'status' => $this->status->value, // Enum support
        ];
    }
}

```

---

## 5. Architectural Safeguards (Mago & Pest)

This section corrects the previous misunderstanding. **Mago** is a static analysis and linting tool configured via `mago.toml`, not a runtime PHP test class. We will use **Mago** for static structural rules and **Pest** for runtime logic verification.

### 5.1 Static Analysis: Mago Guard

We configure `mago.toml` to enforce that all Models in the main namespace adhere to our schema contract. This prevents developers from creating models that accidentally default to the `public` schema.

**File:** `mago.toml` (Add to existing configuration)

```toml
# ============================================================================
# Schema Architecture Rules
# ============================================================================

# Rule 1: All Core Models must implement SchemaScopedModel to ensure safe schema resolution
[[guard.structural.rules]]
on = "App\\Models\\**"
target = "class"
must-implement = "App\\Contracts\\SchemaScopedModel"
reason = "All core models must be schema-aware to support the Hybrid Schema Architecture."
# Exclude external packages or specific models if necessary
exclude = [
    "App\\Models\\Domain", # Extends Stancl\Tenancy
    "App\\Models\\SearchAnalytics" # Explicitly resides in Search schema
]

# Rule 2: SearchAnalytics must reside in the Search namespace or be final
[[guard.structural.rules]]
on = "App\\Models\\SearchAnalytics"
target = "class"
must-be-final = true
reason = "Search infrastructure models should not be extended."

```

### 5.2 Runtime Validation: Pest Architecture Tests

Pest verifies what static analysis cannot: that the traits are actually working and the tables resolve correctly at runtime.

**File:** `tests/Feature/SchemaArchitectureTest.php`

```php
<?php

use App\Models\Team;
use App\Models\User;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

test('database connection prioritizes custom schemas', function () {
    $path = DB::selectOne("SHOW search_path")->search_path;
    $schema = Config::get('database.connections.pgsql.schema');

    expect($path)->toContain($schema)
                 ->toContain('search')
                 ->toContain('public');
})->group('arch', 'schema');

test('core models resolve to the application schema', function () {
    $team = new Team();
    $user = new User();
    $schema = Config::get('database.connections.pgsql.schema');

    expect($team->getTable())->toBe("{$schema}.teams");
    expect($user->getTable())->toBe("{$schema}.users");
})->group('arch', 'schema');

test('domain model is excluded from schema prefixing', function () {
    // Domain extends Stancl BaseDomain and usually expects a specific setup
    $domain = new Domain();

    // Should NOT contain the custom schema prefix 'chrrprr.'
    expect($domain->getTable())->not->toContain('chrrprr.');
})->group('arch', 'schema');

```

---

## 6. Maintenance & Operations

High-performance search indexes in PostgreSQL require maintenance to prevent bloat.

### 6.1 Database Maintenance Command

Create a command to handle index optimization.

**File:** `app/Console/Commands/OptimizeSearch.php`

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeSearch extends Command
{
    protected $signature = 'search:optimize';
    protected $description = 'Vacuum and analyze search tables and indexes';

    public function handle(): void
    {
        $this->info('Optimizing Search Schema...');

        // Analyze specifically updates statistics for the query planner
        // crucial for the complex GIN/GIST indexes to work efficiently.
        DB::statement("VACUUM (ANALYZE) search.search_logs");

        // Optimize the main teams table specifically for the vector column
        $appSchema = config('database.connections.pgsql.schema');
        DB::statement("VACUUM (ANALYZE) \"{$appSchema}\".teams");

        $this->info('Search optimization complete.');
    }
}

```

### 6.2 Schedule

Add to `routes/console.php` or `app/Console/Kernel.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Run nightly to keep indexes efficient without locking tables (standard VACUUM)
Schedule::command('search:optimize')->dailyAt('02:00');

```

---

## 7. Potential "Gotchas"

1. **Join Queries:** When joining `User` (in `chrrprr`) with `SearchAnalytics` (in `search`), you **must** ensure both models define their table with the schema prefix. Eloquent usually handles this if `getTable()` is correctly overridden, but raw DB queries (`DB::table('users')`) will default to the search path order. Always use `Model::getTable()` in raw queries.
2. `**pg_dump` Failures:** Standard backup tools might default to `public` schema only. Ensure your CI/CD and backup scripts explicitly use the `--schema=chrrprr --schema=search` flags (as configured in `config/database.php`).
3. **JSON vs Text:** The `Team` model uses JSON for `name`. Standard `LIKE` queries will fail or produce noise. You strictly rely on `->>'en'` extraction in your indexes. If you add a new locale, you must update the Generated Column definition via a migration.
4. **Migration Rollbacks:** Dropping a schema with `CASCADE` (as seen in the infrastructure migration) destroys **everything** inside it, including data. Be extremely careful with `migrate:fresh` in production contexts.
5. **External Packages:** Packages like `spatie/laravel-permission` or `stancl/tenancy` often assume tables are in the default search path. You may need to publish their migrations and manually prepend `chrrprr.` to the table names or configure them to use the dynamic schema connection.

---

## 8. GIN vs RUM Comparative Analysis for weighted full-text search

Based on your project’s requirement for **weighted full-text search** on `Team` and `User` models using JSON attributes, here is a comparative analysis of using **GIN** (Generalized Inverted Index) versus **RUM** indexes, followed by a weighted recommendation.

### 8.1. Executive Summary

- **GIN:** The standard, built-in choice. Best for boolean search (contains/doesn't contain). Slower at *ranking* (sorting by relevance) on large datasets.
- **RUM:** An extension. Evolution of GIN. Stores positional information in the index. Significantly faster at *ranking* but slower at writing and requires custom installation.

---

### 8.2. GIN (Generalized Inverted Index)

GIN is the default PostgreSQL index type for text search (`tsvector`) and JSONB structures. It works by mapping individual terms (lexemes) to a list of rows that contain them.

#### 8.2.1. Pros

- **Native & Standard:** GIN is part of the PostgreSQL core distribution. It works immediately on AWS RDS, DigitalOcean, and local Docker containers without custom builds.
- **Fast "Contains" Queries:** It is exceptionally fast at filtering rows where a keyword exists (`WHERE search_vector @@ to_tsquery(...)`).
- **Write Performance:** While slower than a B-Tree, GIN is significantly faster to write/update than RUM.
- **Smaller Footprint:** It stores less metadata per entry than RUM, saving disk space.

#### 8.2.2. Cons

- **Slow Ranking:** GIN does **not** store position information in the index. To sort results by relevance (`ORDER BY ts_rank(...)`), Postgres must fetch *every* matching row from the heap (main table), read the `tsvector` data, and calculate the rank on the CPU.
- **Performance Cliff:** On a query matching 100,000 rows, GIN finds them instantly but retrieving and sorting them by rank can take seconds.

**Context for your project:**
Your migration defines a generated `tsvector` column. GIN handles this natively.

### 8.3. RUM (Rum Access Method)

RUM is an extension (`pg_rum`) designed to address GIN's ranking limitations. It stores the **positions** of terms and their **weights** (A, B, C, D) directly inside the inverted index.

#### 8.3.1. Pros

- **Index-Assisted Ranking:** RUM supports the `<=>` (distance) operator. It can return the "top 10 most relevant" results without reading the heap for non-matches.
- **Weighted Search Optimization:** Since your `Team` search heavily relies on weights (Name='A', Bio='B'), RUM can calculate relevance using only the index.
- **Faster Phrase Search:** Searching for exact phrases (e.g., "Software Engineering") is faster because position data is pre-calculated in the index.

#### 8.3.2. Cons

- **Infrastructure Friction:** It is an **extension**. It is not available on all managed database providers (e.g., restricted on some tiers of Google Cloud SQL or Azure). You may need to manage your own Postgres instance or strictly verify provider support.
- **Write Amplification:** RUM indexes are significantly larger (sometimes 2-3x bigger than GIN) and slower to update because they store position offsets for every term.
- **Slow Build Times:** Re-indexing a RUM index takes considerably longer than GIN.

---

### 8.4. %-Weighted Recommendation

For your specific `User` and `Team` search architecture:

#### 8.4.1. **Recommendation: 90% GIN**

**Why?**

1. **Dataset Size:** `Team` and `User` tables rarely reach the "millions of rows" scale where GIN's ranking penalty becomes noticeable. For datasets under ~500k rows, GIN + `ts_rank` is effectively instantaneous (sub-50ms).
2. **Maintenance:** You are using standard migrations and likely standard CI/CD. Relying on a third-party extension (`pg_rum`) introduces a DevOps dependency that can break upgrades or limit hosting choices.
3. **JSONB Compatibility:** Your models rely heavily on JSONB for translations. GIN is the native standard for `jsonb_ops`.

#### 8.4.2. **Recommendation: 10% RUM**

**Why?**

1. **Strict Ranking Requirements:** If your application is *primarily* a search engine (like an internal Google) and users obsess over result ordering.
2. **Massive Scale:** If you anticipate >1 Million Teams/Users and need to sort them by relevance instantly.

#### 8.4.3. Implementation Strategy

Stick to the **GIN** implementation provided in your architecture document. If you notice search slowness in the future (queries taking >200ms), you can optimize without changing the schema by:

1. **Limiting the Candidate Set:** Don't rank *all* matches.

```sql
-- GIN Optimization: Only rank the most recent or active users
WHERE search_vector @@ query AND status = 'active'

```

1. **Covering Indexes:** Ensure the GIN index is on the generated column (which you have already done).

**Verdict:** Proceed with **GIN**. The complexity cost of RUM outweighs the performance benefits for this specific domain model.

---

## 9. Top 10 Future Enhancements

1. **Multi-Language Search Vectors:** Update the generated column to concatenate `name->>'es'`, `name->>'fr'`, etc., into the `tsvector` with different language configurations.
2. **Materialized Views for Reporting:** Create a Materialized View in the `search` schema that aggregates `User` and `Team` stats, refreshing it periodically to offload complex analytical queries from the main `chrrprr` transactional schema.
3. **Cross-Schema Foreign Keys:** While possible, avoid strict Foreign Key constraints between `chrrprr` and `search` schemas to allow the search schema to be treated as ephemeral (can be dropped/rebuilt without breaking app integrity).
4. **pg_stat_statements Monitoring:** Enable this extension to specifically monitor if queries against `search_vector` are hitting the GIN index or performing sequential scans.
5. **Soft Deletes Handling:** Modify the generated `tsvector` column to check `deleted_at`. If `deleted_at IS NOT NULL`, set the vector to empty so soft-deleted items automatically disappear from search results without extra logic.
6. **Highlighting Results:** Use `ts_headline()` in your query to return snippets of the `bio` with the search terms highlighted (e.g., wrapped in `<b>` tags).
7. **Custom Ranking Algorithms:** Implement a function in PostgreSQL that takes `user_context` into account (e.g., boost teams in the user's current `Enterprise`).
8. **Automated Re-indexing:** For very high write environments, `GIN` indexes can get bloated. A scheduled `REINDEX CONCURRENTLY` (carefully managed) can reclaim space better than VACUUM.
9. **Query Expansion:** Implement a thesaurus dictionary in Postgres to automatically expand searches (e.g., searching "dev" also matches "developer" and "engineering").
10. **ReadOnly Search Replica:** Route all `Team::search()` read queries to a read-only Postgres replica to isolate search load from transactional write load.
