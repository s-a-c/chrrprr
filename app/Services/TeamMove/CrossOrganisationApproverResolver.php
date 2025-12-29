<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamOrganisationFinderService;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

final readonly class CrossOrganisationApproverResolver implements ApproverResolverInterface
{
    public function __construct(
        private TeamOrganisationFinderService $organisationFinder,
    ) {}

    /**
     * Resolve approvers for cross-organisation moves.
     *
     * @return array<int>
     */
    public function resolve(Team $team, ?Team $newParent): array
    {
        $sourceOrg = $this->organisationFinder->findOrganisation($team);
        $targetOrg = $newParent instanceof Team ? $this->organisationFinder->findOrganisation($newParent) : null;

        $approvers = [];
        if ($sourceOrg instanceof Team) {
            $sourceAdmins = $this->getOrganisationAdmins($sourceOrg);
            $approvers = array_merge($approvers, $sourceAdmins);
        }

        if ($targetOrg && $targetOrg->id !== $sourceOrg?->id) {
            $targetAdmins = $this->getOrganisationAdmins($targetOrg);
            $approvers = array_merge($approvers, $targetAdmins);
        }

        return array_unique($approvers);
    }

    /**
     * Get organisation admin user IDs.
     *
     * @return array<int>
     */
    private function getOrganisationAdmins(Team $organisation): array
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($organisation->id);

        try {
            try {
                return User::query()
                    ->role('organisation_admin')
                    ->pluck('id')
                    ->toArray();
            } catch (RoleDoesNotExist) {
                return [];
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
