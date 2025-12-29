<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;

final class DescendantCountRule implements ApprovalRuleInterface
{
    public function requiresApproval(Team $team, ?Team $newParent, Team $enterprise): bool
    {
        $threshold = $enterprise->move_approval_descendant_threshold ?? 10;
        $descendantCount = $this->getDescendantCount($team);

        return $descendantCount >= $threshold;
    }

    /**
     * Get the count of all descendants (recursive).
     *
     * @psalm-return int<min, max>
     */
    private function getDescendantCount(Team $team): int
    {
        $count = 0;
        $children = $team->children()->withoutGlobalScopes()->get();

        foreach ($children as $child) {
            $count++; // Count the direct child
            $count += $this->getDescendantCount($child); // Recursively count descendants
        }

        return $count;
    }
}
