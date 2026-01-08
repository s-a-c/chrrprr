<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Builders\TeamBuilder;
use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Scope;

trait HasTeamSearch
{
    /**
     * Typo-tolerant fuzzy search scope using pg_trgm.
     *
     * @psalm-suppress PossiblyUnusedMethod Called dynamically via Laravel's Scope attribute
     *
     * @psalm-return TeamBuilder<Team>
     */
    #[Scope]
    protected function fuzzySearch(TeamBuilder $query, string $term): TeamBuilder
    {
        // Extract English name from JSON for comparison and cast to text for similarity operator
        // Use similarity with a lower threshold (0.1) to catch more matches
        // Cast name to JSONB first, then extract, then cast to text
        return $query->whereRaw("similarity((name::jsonb->>'en')::text, ?::text) > 0.1", [$term])
            ->orderByRaw("similarity((name::jsonb->>'en')::text, ?::text) DESC", [$term]);
    }

    /**
     * Full-text search scope using weighted search_vector.
     *
     * @psalm-suppress PossiblyUnusedMethod Called dynamically via Laravel's Scope attribute
     *
     * @psalm-return TeamBuilder<Team>
     */
    #[Scope]
    protected function fullTextSearch(TeamBuilder $query, string $term): TeamBuilder
    {
        // Use plainto_tsquery for better handling of plain text search terms
        return $query->whereRaw('search_vector @@ plainto_tsquery(?, ?)', ['english', $term])
            ->orderByRaw('ts_rank(search_vector, plainto_tsquery(?, ?)) DESC', ['english', $term]);
    }
}
