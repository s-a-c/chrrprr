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

        Schema::table('teams', function (Blueprint $table) use ($schema): void {
            // 1. Weighted Full-Text Search Vector (A = Name, B = Bio)
            // We extract the 'en' key from the JSON columns.
            // Note: extract_json_text function is created in add_hybrid_search_to_users migration
            // Check if column already exists
            $columnExists = DB::selectOne("
                SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = ? AND table_name = 'teams' AND column_name = 'search_vector'
            ", [$schema]);

            if (! $columnExists) {
                DB::statement("
                    ALTER TABLE \"{$schema}\".teams
                    ADD COLUMN search_vector tsvector
                    GENERATED ALWAYS AS (
                        setweight(to_tsvector('english', public.unaccent_immutable(public.extract_json_text(name::text, 'en'))), 'A') ||
                        setweight(to_tsvector('english', public.unaccent_immutable(public.extract_json_text(bio::text, 'en'))), 'B')
                    ) STORED
                ");
            }

            // 2. GIN Index for blazing fast Full-Text Search
            DB::statement("CREATE INDEX IF NOT EXISTS teams_fts_idx ON \"{$schema}\".teams USING GIN(search_vector)");

            // 3. Trigram Index for Fuzzy Matching (Typo tolerance) on the name
            DB::statement("CREATE INDEX IF NOT EXISTS teams_fuzzy_idx ON \"{$schema}\".teams USING GIST (public.extract_json_text(name::text, 'en') gist_trgm_ops)");
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

        Schema::table('teams', function (Blueprint $table) use ($schema): void {
            DB::statement("DROP INDEX IF EXISTS \"{$schema}\".teams_fuzzy_idx");
            DB::statement("DROP INDEX IF EXISTS \"{$schema}\".teams_fts_idx");
            $table->dropColumn('search_vector');
        });
    }
};
