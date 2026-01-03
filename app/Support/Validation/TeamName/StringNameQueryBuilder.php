<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * Query builder for string-based team names.
 *
 * Uses collections to apply constraints across multiple locales.
 */
final class StringNameQueryBuilder implements NameQueryBuilderInterface
{
    private const array LOCALES = ['en', 'es', 'fr', 'de'];

    #[Override]
    /**
     * @param  string|string[]  $name
     *
     * @psalm-param ''|'Test Name'|'Test Organisation'|list{'not', 'a', 'string'} $name
     */
    public function applyConstraints(Builder $query, array|string $name, Team $team): void
    {
        if (! is_string($name) || $name === '') {
            return;
        }

        $connection = $query->getConnection();
        $driver = $connection->getDriverName();

        $query->where(static function (Builder $q) use ($name, $driver): void {
            collect(self::LOCALES)
                ->each(static function (string $locale) use ($q, $name, $driver): void {
                    if ($driver === 'pgsql') {
                        // Cast to JSONB for PostgreSQL to handle JSON operations on VARCHAR columns
                        $q->orWhereRaw("CAST(name AS JSONB)->>'{$locale}' = ?", [$name]);
                    } else {
                        // For other databases, use standard JSON path syntax
                        $q->orWhere("name->{$locale}", $name);
                    }
                });

            if ($driver === 'pgsql') {
                // For PostgreSQL, use JSONB contains operator
                $q->orWhereRaw('CAST(name AS JSONB) @> ?::jsonb', [json_encode($name)]);
            } else {
                $q->orWhereJsonContains('name', $name);
            }
        });
    }
}
