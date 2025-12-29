<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder for array-based (translatable) team names.
 *
 * Uses collections to filter and apply constraints without nested conditionals.
 */
final class ArrayNameQueryBuilder implements NameQueryBuilderInterface
{
    public function applyConstraints(Builder $query, mixed $names, Team $team): void
    {
        if (! is_array($names)) {
            return;
        }

        $query->where(static function (Builder $q) use ($names): void {
            collect($names)
                ->filter() // Automatically removes null/empty values
                ->each(fn ($value, $locale) => $q->orWhere("name->{$locale}", $value));
        });
    }
}
