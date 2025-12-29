<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Enums\TeamType;
use App\Models\Team;

final class CrossOrganisationRule implements ApprovalRuleInterface
{
    public function requiresApproval(Team $team, ?Team $newParent, Team $enterprise): bool
    {
        if (! ($enterprise->move_approval_require_cross_org ?? true)) {
            return false;
        }

        return $this->isCrossOrganisationMove($team, $newParent);
    }

    /**
     * Check if a move is cross-organisation.
     */
    private function isCrossOrganisationMove(Team $team, ?Team $newParent): bool
    {
        $sourceOrg = $this->findOrganisation($team);
        $targetOrg = $newParent instanceof Team ? $this->findOrganisation($newParent) : null;

        if (! $sourceOrg || ! $targetOrg) {
            return false;
        }

        return $sourceOrg->id !== $targetOrg->id;
    }

    /**
     * Find the organisation that contains a team.
     */
    private function findOrganisation(Team $team): ?Team
    {
        $current = $team;

        while ($current) {
            if ($current->type === TeamType::ORGANISATION) {
                return $current;
            }

            $current = $current->parent;
        }

        return null;
    }
}
