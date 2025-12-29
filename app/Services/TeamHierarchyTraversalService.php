<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for traversing team hierarchies using collection pipelines.
 */
final readonly class TeamHierarchyTraversalService
{
    /**
     * Check if a team is a descendant of another team using collection pipeline.
     *
     * Replaces while loop with functional collection pattern.
     */
    public function isDescendantOf(Team $team, Team $ancestor): bool
    {
        return $this->getAncestry($team)
            ->contains('id', $ancestor->id);
    }

    /**
     * Get the depth of a team in the hierarchy using collection count.
     *
     * Replaces while loop with functional pipeline.
     *
     * @psalm-return int<1, max>
     */
    public function getDepth(Team $team): int
    {
        return $this->getAncestry($team)->count() + 1;
    }

    /**
     * Get all ancestors of a team using collection pipeline.
     *
     * This replaces the imperative while loop with a functional collection pattern.
     * Uses a recursive collection approach to build the ancestry chain.
     */
    public function getAncestry(Team $team): Collection
    {
        return $this->buildAncestryCollection($team->parent_id);
    }

    /**
     * Get all descendants of a team (for batch operations).
     *
     * Uses recursive collection mapping instead of imperative loops.
     */
    public function getDescendants(Team $team): Collection
    {
        $children = $team->children()->withoutGlobalScopes()->get();

        if ($children->isEmpty()) {
            return collect();
        }

        return $children
            ->merge(
                $children->flatMap(fn ($child) => $this->getDescendants($child))
            );
    }

    /**
     * Recursively build ancestry collection using functional approach.
     */
    private function buildAncestryCollection(?int $parentId): Collection
    {
        if (! $parentId) {
            return collect();
        }

        $parent = DB::table('teams')
            ->where('id', $parentId)
            ->whereNull('deleted_at')
            ->first();

        if (! $parent) {
            return collect();
        }

        $parentModel = Team::query()->withoutGlobalScopes()->find($parent->id);

        if (! $parentModel) {
            return collect();
        }

        // Recursively build collection, prepending current parent
        return $this->buildAncestryCollection($parent->parent_id)
            ->prepend($parentModel);
    }
}
