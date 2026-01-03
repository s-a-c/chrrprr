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
        if ($schema && str_contains((string) $schema, '${APP_ID}')) {
            /** @var mixed $appIdRaw */
            $appIdRaw = env('APP_ID', 'chrrprr');
            $appId = is_string($appIdRaw) ? $appIdRaw : 'chrrprr';
            $schema = str_replace('${APP_ID}', $appId, $schema);
        }

        // Ensure we have a valid schema name
        if (in_array($schema, [null, '', '${APP_ID}'], true)) {
            /** @var mixed $appIdRaw */
            $appIdRaw = env('APP_ID', 'chrrprr');
            $appId = is_string($appIdRaw) && $appIdRaw !== '' ? $appIdRaw : 'public';
            $schema = $appId;
        }

        if ($driver === 'pgsql' && $schema !== 'public' && ! str_contains($table, '.')) {
            return "{$schema}.{$table}";
        }

        return $table;
    }
}
