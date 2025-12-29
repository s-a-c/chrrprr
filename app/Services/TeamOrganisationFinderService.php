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
            ->first(fn (Team $ancestor) => $ancestor->type === TeamType::ORGANISATION);
    }

    /**
     * Check if a move is cross-organisation using collection comparison.
     */
    public function isCrossOrganisationMove(Team $team, ?Team $newParent): bool
    {
        $sourceOrg = $this->findOrganisation($team);
        $targetOrg = $newParent ? $this->findOrganisation($newParent) : null;

        if (! $sourceOrg || ! $targetOrg) {
            return false;
        }

        return $sourceOrg->id !== $targetOrg->id;
    }

    /**
     * Get ancestry using collection pipeline.
     */
    private function getAncestry(Team $team): Collection
    {
        $ancestors = collect();
        $current = $team->parent;

        // Build ancestry collection using functional approach
        while ($current) {
            $ancestors->push($current);

            if ($current->type === TeamType::ORGANISATION) {
                break;
            }

            $current = $current->parent;
        }

        return $ancestors;
    }
}
