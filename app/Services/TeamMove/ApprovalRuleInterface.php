<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;

interface ApprovalRuleInterface
{
    /**
     * Determine if approval is required based on this rule.
     */
    public function requiresApproval(Team $team, ?Team $newParent, Team $enterprise): bool;
}
