<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use Override;

final class DescendantCountRule implements ApprovalRuleInterface
{
    #[Override]
    public function requiresApproval(Team $team, ?Team $newParent, Team $enterprise): bool
    {
        $threshold = $enterprise->move_approval_descendant_threshold ?? 10;
        $descendantCount = $this->getDescendantCount($team);

        return $descendantCount >= $threshold;
    }

    /**
     * Get the count of all descendants (recursive).
     *
     * Uses collection sum with recursion for functional approach.
     *
     * @psalm-return int<min, max>
     */
    private function getDescendantCount(Team $team): int
    {
        return $team->children()
            ->withoutGlobalScopes()
            ->get()
            ->sum(fn (Team $child): int => 1 + $this->getDescendantCount($child)
            );
    }
}
