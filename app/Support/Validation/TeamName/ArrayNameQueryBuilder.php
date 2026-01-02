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

        $query->where(static function (Builder $q) use ($names): void {
            collect($names)
                ->filter() // Automatically removes null/empty values
                ->each(static fn ($value, $locale) => $q->orWhere("name->{$locale}", $value));
        });
    }
}
