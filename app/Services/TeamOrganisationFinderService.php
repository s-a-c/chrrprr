<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Service for finding organisations in team hierarchies using collections.
 */
final readonly class TeamOrganisationFinderService
{
    /**
     * Find the organisation that contains a team using collection pipeline.
     *
     * Replaces while loop with functional collection pattern.
     */
    public function findOrganisation(Team $team): ?Team
    {
        // Check if the team itself is an organisation
        if ($team->type === TeamType::ORGANISATION) {
            return $team;
        }

        return $this->getAncestry($team)
            ->first(static fn (Team $ancestor): bool => $ancestor->type === TeamType::ORGANISATION);
    }

    /**
     * Check if a move is cross-organisation using collection comparison.
     */
    public function isCrossOrganisationMove(Team $team, ?Team $newParent): bool
    {
        $sourceOrg = $this->findOrganisation($team);
        $targetOrg = $newParent instanceof Team ? $this->findOrganisation($newParent) : null;

        if (! $sourceOrg || ! $targetOrg) {
            return false;
        }

        return $sourceOrg->id !== $targetOrg->id;
    }

    /**
     * Get ancestry using functional recursive collection pipeline.
     *
     * Stops when organisation is found, similar to TeamHierarchyTraversalService pattern.
     */
    private function getAncestry(Team $team): Collection
    {
        return $this->buildAncestryUntilOrganisation($team->parent);
    }

    /**
     * Recursively build ancestry collection until organisation is found.
     */
    private function buildAncestryUntilOrganisation(?Team $current): Collection
    {
        if (! $current instanceof Team) {
            return collect();
        }

        if ($current->type === TeamType::ORGANISATION) {
            return collect([$current]);
        }

        return $this->buildAncestryUntilOrganisation($current->parent)
            ->prepend($current);
    }
}
