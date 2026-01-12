<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use Override;

final class DepthChangeRule implements ApprovalRuleInterface
{
    #[Override]
    public function requiresApproval(Team $team, ?Team $newParent, Team $enterprise): bool
    {
        $threshold = $enterprise->move_approval_depth_change_threshold ?? 2;
        $currentDepth = $team->getDepth();
        $newDepth = $newParent instanceof Team ? $newParent->getDepth() + 1 : 1;
        $depthChange = abs($newDepth - $currentDepth);

        return $depthChange >= $threshold;
    }
}
