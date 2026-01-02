<?php

declare(strict_types=1);

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

        // Handle template variables like ${APP_ID}
        if ($schema && str_contains($schema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $schema = str_replace('${APP_ID}', $appId, $schema);
        }

        // Ensure we have a valid schema name
        if (empty($schema) || $schema === '' || $schema === '${APP_ID}') {
            $schema = env('APP_ID', 'chrrprr') ?: 'public';
        }

        if ($driver === 'pgsql' && $schema && $schema !== 'public' && ! str_contains($table, '.')) {
            return "{$schema}.{$table}";
        }

        return $table;
    }
}
