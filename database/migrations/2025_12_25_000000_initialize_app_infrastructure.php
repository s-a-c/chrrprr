<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Get schema names with fallbacks - handle both config and direct env access
        $appSchema = trim((string) (Config::get('database.connections.pgsql.schema') ?: env('DB_SCHEMA', 'public')));
        $searchSchema = trim((string) (Config::get('database.connections.pgsql.search_schema') ?: env('DB_SEARCH_SCHEMA', 'search')));

        // Handle template variables like ${APP_ID} - replace with actual value or fallback
        if (str_contains($appSchema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $appSchema = str_replace('${APP_ID}', $appId, $appSchema);
        }

        if (str_contains($searchSchema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $searchSchema = str_replace('${APP_ID}', $appId, $searchSchema);
        }

        // Ensure we have valid schema names (handle empty strings and invalid values)
        if (empty($appSchema) || $appSchema === '' || $appSchema === '${APP_ID}') {
            $appSchema = env('APP_ID', 'chrrprr') ?: 'public';
        }

        if (empty($searchSchema) || $searchSchema === '' || $searchSchema === '${APP_ID}') {
            $searchSchema = 'search';
        }

        // 1. Extensions in 'public' so they are globally accessible
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm SCHEMA public');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent SCHEMA public');

        // 2. Create physical schemas (only if not 'public')
        if ($appSchema && $appSchema !== 'public') {
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$appSchema}\"");
        }

        if ($searchSchema && $searchSchema !== 'public') {
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$searchSchema}\"");
        }

        // 3. Set session path to ensure subsequent migrations find these schemas
        if ($appSchema && $appSchema !== 'public') {
            DB::statement("SET search_path TO \"{$appSchema}\", \"{$searchSchema}\", public");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

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
