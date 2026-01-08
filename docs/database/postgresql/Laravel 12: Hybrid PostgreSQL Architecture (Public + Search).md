# Laravel 12: Hybrid PostgreSQL Architecture (Public + Search)

This final "Hybrid" blueprint provides the perfect balance. Your application remains 100% standard Laravel (living in public), while your heavy search infrastructure is isolated in a dedicated search schema. This keeps your main database clean, your testing suite fast, and your search functionality professional.

This architecture uses the standard `public` schema for your application tables to maximize compatibility and the `search` schema for isolated, high-performance Laravel Scout indexes.

---

## 1. Database Configuration
We keep the `search_path` simple: check the app tables first, then the search schema, then extensions.

**File: `.env`**

```env
DB_SCHEMA=public
DB_SEARCH_SCHEMA=search
SCOUT_DRIVER=database

```

**File: `config/database.php`**

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'laravel'),
    // ...
    'schema' => env('DB_SCHEMA', 'public'),
    'search_schema' => env('DB_SEARCH_SCHEMA', 'search'),

    // Path: public (tables), search (indexes), public (extensions)
    'search_path' => implode(',', [
        env('DB_SCHEMA', 'public'),
        env('DB_SEARCH_SCHEMA', 'search'),
        'public'
    ]),

    'dump' => [
        'add_extra_args' => '--schema=public --schema=' . env('DB_SEARCH_SCHEMA', 'search') . ' --no-owner',
    ],
],

```

---

## 2. Infrastructure Migration
This migration handles the "engine room" setup: enabling fuzzy search and creating the search-specific container.

**File: `database/migrations/2026_01_01_000000_initialize_search_infrastructure.php`**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        if (DB::getDriverName() !== 'pgsql') return;

        // 1. Extensions: Best installed in 'public' for global access
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm SCHEMA public');

        // 2. Search Schema: Isolation for indexes and search views
        DB::statement('CREATE SCHEMA IF NOT EXISTS "search"');
    }

    public function down(): void {
        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement('DROP SCHEMA IF EXISTS "search" CASCADE');
    }
};

```

---

## 3. High-Performance Search Implementation

We use **Weighted Full-Text Search** for accuracy and **Trigram Indexes** for typo tolerance.

**Migration for `posts` Table:**

```php
Schema::table('posts', function (Blueprint $table) {
    // Generated column: Auto-updates when title or body changes
    DB::statement("
        ALTER TABLE posts
        ADD COLUMN search_vector tsvector
        GENERATED ALWAYS AS (
            setweight(to_tsvector('english', coalesce(title, '')), 'A') ||
            setweight(to_tsvector('english', coalesce(body, '')), 'B')
        ) STORED
    ");

    // FTS Index (Standard Search)
    DB::statement("CREATE INDEX posts_fts_idx ON posts USING GIN(search_vector)");

    // Trigram Index (Fuzzy/Typo Search)
    DB::statement("CREATE INDEX posts_fuzzy_idx ON posts USING GIST (title gist_trgm_ops)");
});

```

---

## 4. Simplified Model (No Traits Required)

Because your tables are in `public`, you don't need any custom schema logic in your Eloquent models.

**File: `app/Models/Post.php`**

```php
namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model {
    use Searchable;

    public function toSearchableArray(): array {
        return ['title' => $this->title, 'body' => $this->body];
    }

    /**
     * Typo-tolerant search
     * Usage: Post::fuzzySearch('Laraavl')->get();
     */
    public function scopeFuzzySearch(Builder $query, string $term): Builder {
        return $query->whereRaw('title % ?', [$term])
                     ->orderByRaw('similarity(title, ?) DESC', [$term]);
    }
}

```

---

## 5. Summary of Schema Resolution

| Action | Logic | Result |
| :--- | :--- | :--- |
| **Standard Query** | `SELECT * FROM posts` | Found in `public`. |
| **Scout Search** | Internal DB Engine query | Resolves via `search_vector` on table. |
| **Fuzzy Match** | `similarity(a, b)` | Found in `public` (Extensions). |
| **Isolation** | Vacuum/Reindex `search` | Keeps `public` lean and fast. |

---

## 6. Verification

Run this command to ensure the driver has loaded the path correctly.

**Command:** `php artisan tinker --execute="DB::selectOne('SHOW search_path')"`
**Expected Result:** `public, search, public`

## 7. Final Benefits of the Hybrid Approach:

1. **Standard Laravel:** Your models are clean. No traits, no contracts, no complex inheritance.
2. **Tooling:** TablePlus, Nova, and Pulse work perfectly without configuration.
3. **Clean Search:** All GIN and GIST indexes are logically categorized.
4. **Testing:** SQLite `:memory:` works out of the box because `public` is the default for both.
