<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Builders\UserBuilder;
use Illuminate\Database\Eloquent\Attributes\Scope;

trait HasUserSearch
{
    /**
     * Typo-tolerant fuzzy search scope using pg_trgm.
     *
     * @psalm-suppress PossiblyUnusedMethod Called dynamically via Laravel's Scope attribute
     */
    #[Scope]
    protected function fuzzySearch(UserBuilder $query, string $term): UserBuilder
    {
        // Use similarity with a lower threshold (0.1) to catch more matches
        // The % operator uses default threshold of 0.3 which is too strict
        return $query->whereRaw('similarity(name, ?) > 0.1', [$term])
            ->orderByRaw('similarity(name, ?) DESC', [$term]);
    }

    /**
     * Full-text search scope using weighted search_vector.
     *
     * @psalm-suppress PossiblyUnusedMethod Called dynamically via Laravel's Scope attribute
     */
    #[Scope]
    protected function fullTextSearch(UserBuilder $query, string $term): UserBuilder
    {
        // Use plainto_tsquery for better handling of plain text search terms
        // Ensure search_vector is not NULL (generated columns should always have values)
        return $query->whereNotNull('search_vector')
            ->whereRaw('search_vector @@ plainto_tsquery(?, ?)', ['english', $term])
            ->orderByRaw('ts_rank(search_vector, plainto_tsquery(?, ?)) DESC', ['english', $term]);
    }
}
