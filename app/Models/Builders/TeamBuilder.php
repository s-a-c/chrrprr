<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Custom builder for Team model with complex query methods.
 *
 * @template TModelClass of \App\Models\Team
 *
 * @extends Builder<TModelClass>
 */
class TeamBuilder extends Builder
{
    /**
     * Scope the query to the current user's context using a Recursive CTE.
     * This replaces the complex logic previously found in the Model scope.
     *
     * @psalm-return static<TModelClass>
     */
    public function inContext(?User $user = null): static
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $user->current_context_id) {
            return $this;
        }

        $contextId = (int) $user->current_context_id;

        return $this->whereIn('id', static function (\Illuminate\Database\Query\Builder $query) use ($contextId): void {
            // Recursive Common Table Expression (CTE)
            $query->select('id')->from(DB::raw("(
                    WITH RECURSIVE descendants AS (
                        SELECT id, parent_id FROM teams WHERE id = {$contextId}
                        UNION ALL
                        SELECT t.id, t.parent_id FROM teams t
                        JOIN descendants d ON t.parent_id = d.id
                    )
                    SELECT id FROM descendants
                ) as contextual_teams"));
        });
    }

    /**
     * Explicitly remove context scope (for clarity in code).
     *
     * @psalm-return static<TModelClass>
     */
    public function withoutContextScope(): static
    {
        // Explicitly communicates intent, even if it does nothing functionally
        // in a custom builder (vs a global scope).
        return $this;
    }
}
