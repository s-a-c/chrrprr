<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use App\Services\TeamOrganisationFinderService;

final class CrossOrganisationRule implements ApprovalRuleInterface
{
    public function __construct(
        private TeamOrganisationFinderService $organisationFinder,
    ) {}

    public function requiresApproval(Team $team, ?Team $newParent, Team $enterprise): bool
    {
        if (! ($enterprise->move_approval_require_cross_org ?? true)) {
            return false;
        }

        return $this->organisationFinder->isCrossOrganisationMove($team, $newParent);
    }
}
