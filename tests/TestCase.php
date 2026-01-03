<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Override;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF token validation in tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    /**
     * Set up PostgreSQL schemas and functions before migrations run.
     * This method is called by RefreshDatabase before refreshTestDatabase(),
     * ensuring schemas exist when migrations execute.
     */
    protected function beforeRefreshingDatabase()
    {
        // Set up PostgreSQL schemas and functions BEFORE migrations run
        // This ensures schemas exist when migrations execute
        if (DB::getDriverName() === 'pgsql') {
            $schema = (string) config('database.connections.pgsql.schema', 'public');
            $searchSchema = (string) config('database.connections.pgsql.search_schema', 'search');

            // Create schemas if they don't exist
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$searchSchema}\"");

            // Set search_path so tables are created in the correct schema
            // Quote schema names to handle hyphens and special characters
            $searchPath = implode(',', [
                "\"{$schema}\"",
                "\"{$searchSchema}\"",
                'public',
            ]);
            DB::statement("SET search_path TO {$searchPath}");

            // Ensure required extensions exist (in public schema)
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

            // Ensure helper functions exist for search_vector generation (in public schema)
            // These functions are used by the GENERATED ALWAYS column expression
            DB::statement('
                CREATE OR REPLACE FUNCTION public.unaccent_immutable(text)
                RETURNS text
                LANGUAGE sql
                IMMUTABLE
                AS $$
                    SELECT unaccent($1)
                $$;
            ');

            DB::statement("
                CREATE OR REPLACE FUNCTION public.extract_json_text(json_text text, key text)
                RETURNS text
                LANGUAGE sql
                IMMUTABLE
                AS \$\$
                    SELECT CASE
                        WHEN json_text IS NULL OR json_text = '' THEN ''
                        ELSE (json_text::jsonb->>key)
                    END
                \$\$;
            ");
        }
    }

    /**
     * Override refreshTestDatabase to handle PostgreSQL schema-specific issues.
     * This ensures tables are dropped safely and migrations run correctly.
     */
    protected function refreshTestDatabase()
    {
        if (DB::getDriverName() === 'pgsql') {
            $schema = (string) config('database.connections.pgsql.schema', 'public');

            // Ensure we're using the correct schema before refresh
            DB::statement("SET search_path TO \"{$schema}\", public");

            // Drop all tables safely using IF EXISTS to avoid errors when tables don't exist
            try {
                // Get all tables in both the custom schema and public schema
                $allTables = DB::select("
                    SELECT schemaname, tablename
                    FROM pg_tables
                    WHERE schemaname IN (?, 'public')
                    AND tablename NOT LIKE 'pg_%'
                ", [$schema]);

                // Drop each table individually with IF EXISTS and CASCADE
                foreach ($allTables as $table) {
                    try {
                        DB::statement("DROP TABLE IF EXISTS \"{$table->schemaname}\".\"{$table->tablename}\" CASCADE");
                    } catch (Exception $e) {
                        // Silently ignore errors - table might already be dropped
                    }
                }
            } catch (Exception $e) {
                // If custom drop fails, fall back to Laravel's method
                // This might fail if tables don't exist, but that's okay
                try {
                    Schema::dropAllTables();
                } catch (Exception $e) {
                    // Continue even if drop fails - migrations will handle it
                }
            }

            // Ensure search_path is set correctly for migrations
            $searchPath = implode(',', [
                "\"{$schema}\"",
                (string) config('database.connections.pgsql.search_schema', 'search'),
                'public',
            ]);
            DB::statement("SET search_path TO {$searchPath}");
        }

        // Call parent to run migrations
        parent::refreshTestDatabase();
    }
}
