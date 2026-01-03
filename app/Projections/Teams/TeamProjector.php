<?php

declare(strict_types=1);

namespace App\Projections\Teams;

use App\Events\Teams\TeamCreated;
use Thunk\Verbs\Attributes\Hooks\Listen;

/**
 * Team Projector.
 *
 * Updates read model projections based on Team domain events.
 * This projector listens to TeamCreated events and updates the Team projection.
 *
 * Note: In the current implementation, the Team model serves as both the
 * write model (created via Eloquent) and read model (query source).
 * This projector demonstrates the pattern for future use cases where
 * separate projection tables might be needed for read optimization.
 */
final class TeamProjector
{
    /**
     * Handle TeamCreated events by updating the projection.
     *
     * This method is called automatically when a TeamCreated event is fired.
     * It can be used to update denormalized read models, cache, or other
     * query-optimized data structures.
     */
    #[Listen(TeamCreated::class)]
    public function onTeamCreated(): void
    {
        // Example: Update a projection table, cache, or search index
        // For now, the Team model itself serves as the read model,
        // so no additional projection logic is needed.
        //
        // Future use cases:
        // - Update a denormalized team_projection table
        // - Refresh search index (Laravel Scout)
        // - Update cache
        // - Update materialized views
    }
}
