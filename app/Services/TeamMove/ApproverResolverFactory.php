<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use App\Services\TeamOrganisationFinderService;

/**
 * Factory for creating approver resolvers based on move type.
 */
final readonly class ApproverResolverFactory
{
    public function __construct(
        private TeamOrganisationFinderService $organisationFinder,
    ) {}

    /**
     * Get the appropriate approver resolver based on move type.
     */
    public function getResolver(Team $team, ?Team $newParent): ApproverResolverInterface
    {
        if ($this->organisationFinder->isCrossOrganisationMove($team, $newParent)) {
            return new CrossOrganisationApproverResolver($this->organisationFinder);
        }

        return new SameOrganisationApproverResolver($this->organisationFinder);
    }
}
