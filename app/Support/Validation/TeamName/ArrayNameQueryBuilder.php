<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * Query builder for array-based (translatable) team names.
 *
 * Uses collections to filter and apply constraints without nested conditionals.
 */
final class ArrayNameQueryBuilder implements NameQueryBuilderInterface
{
    #[Override]
    /**
     * @param  (null|string)[]|string  $names
     *
     * @psalm-param 'not an array'|array{en: 'Existing Org'|null, es?: '', fr?: 'Valid Name'} $names
     */
    public function applyConstraints(Builder $query, array|string $names, Team $team): void
    {
        if (! is_array($names)) {
            return;
        }

        $connection = $query->getConnection();
        $driver = $connection->getDriverName();

        $query->where(static function (Builder $q) use ($names, $driver): void {
            collect($names)
                ->filter() // Automatically removes null/empty values
                ->each(static function (mixed $value, int|string $locale) use ($q, $driver): void {
                    if ($driver === 'pgsql') {
                        // Cast to JSONB for PostgreSQL to handle JSON operations on VARCHAR columns
                        $q->orWhereRaw("CAST(name AS JSONB)->>'{$locale}' = ?", [$value]);
                    } else {
                        // For other databases, use standard JSON path syntax
                        $q->orWhere("name->{$locale}", $value);
                    }
                });
        });
    }
}
