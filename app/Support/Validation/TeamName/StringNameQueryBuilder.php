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

        $query->where(static function (Builder $q) use ($name): void {
            collect(self::LOCALES)
                ->each(static fn (string $locale) => $q->orWhere("name->{$locale}", $name));

            $q->orWhereJsonContains('name', $name);
        });
    }
}
