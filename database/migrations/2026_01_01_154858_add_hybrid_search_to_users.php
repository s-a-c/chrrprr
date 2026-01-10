<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $schema = config('database.connections.pgsql.schema') ?: env('DB_SCHEMA', 'public');

        // Handle template variables like ${APP_ID}
        if ($schema && str_contains((string) $schema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $schema = str_replace('${APP_ID}', $appId, $schema);
        }

        // Ensure we have a valid schema name
        if (empty($schema) || $schema === '' || $schema === '${APP_ID}') {
            $schema = env('APP_ID', 'chrrprr') ?: 'public';
        }

        Schema::table('users', function (Blueprint $table) use ($schema): void {
            // 1. Weighted Full-Text Search Vector (A = Name, B = Email, C = Bio)
            // We extract the 'en' key from the JSON bio column.
            // Create immutable wrapper functions
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

            // Check if column already exists and is properly configured
            // Use a more robust query that handles schema-qualified table names
            $columnInfo = DB::selectOne("
                SELECT column_name, is_generated
                FROM information_schema.columns
                WHERE table_schema = ? AND table_name = 'users' AND column_name = 'search_vector'
            ", [$schema]);

            // Always ensure the column exists and is properly configured as GENERATED ALWAYS
            // Drop and recreate if it doesn't exist or isn't generated correctly
            if (! $columnInfo || ($columnInfo->is_generated ?? null) !== 'ALWAYS') {
                // Drop column if it exists but isn't generated correctly
                DB::statement("ALTER TABLE \"{$schema}\".users DROP COLUMN IF EXISTS search_vector");

                // Add the GENERATED ALWAYS column
                // Note: GENERATED ALWAYS columns populate automatically for new rows
                // Existing rows may need an UPDATE to trigger generation
                DB::statement("
                    ALTER TABLE \"{$schema}\".users
                    ADD COLUMN search_vector tsvector
                    GENERATED ALWAYS AS (
                        setweight(to_tsvector('english', public.unaccent_immutable(coalesce(name, ''))), 'A') ||
                        setweight(to_tsvector('english', public.unaccent_immutable(coalesce(email, ''))), 'B') ||
                        setweight(to_tsvector('english', public.unaccent_immutable(public.extract_json_text(bio, 'en'))), 'C')
                    ) STORED
                ");

                // Update existing rows to populate search_vector (GENERATED ALWAYS needs a trigger)
                DB::statement("UPDATE \"{$schema}\".users SET name = name WHERE search_vector IS NULL");
            }

            // 2. GIN Index for blazing fast Full-Text Search
            DB::statement("CREATE INDEX IF NOT EXISTS users_fts_idx ON \"{$schema}\".users USING GIN(search_vector)");

            // 3. Trigram Index for Fuzzy Matching (Typo tolerance) on the name
            DB::statement("CREATE INDEX IF NOT EXISTS users_fuzzy_idx ON \"{$schema}\".users USING GIST (name gist_trgm_ops)");
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $schema = config('database.connections.pgsql.schema') ?: env('DB_SCHEMA', 'public');

        // Handle template variables like ${APP_ID}
        if ($schema && str_contains((string) $schema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $schema = str_replace('${APP_ID}', $appId, $schema);
        }

        // Ensure we have a valid schema name
        if (empty($schema) || $schema === '' || $schema === '${APP_ID}') {
            $schema = env('APP_ID', 'chrrprr') ?: 'public';
        }

        Schema::table('users', function (Blueprint $table) use ($schema): void {
            DB::statement("DROP INDEX IF EXISTS \"{$schema}\".users_fuzzy_idx");
            DB::statement("DROP INDEX IF EXISTS \"{$schema}\".users_fts_idx");
            $table->dropColumn('search_vector');
        });

        // Drop the helper functions
        DB::statement('DROP FUNCTION IF EXISTS public.extract_json_text(text, text)');
        DB::statement('DROP FUNCTION IF EXISTS public.unaccent_immutable(text)');
    }
};
