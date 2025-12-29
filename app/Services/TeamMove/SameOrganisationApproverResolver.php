<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamOrganisationFinderService;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

final class SameOrganisationApproverResolver implements ApproverResolverInterface
{
    public function __construct(
        private TeamOrganisationFinderService $organisationFinder,
    ) {}

    /**
     * Resolve approvers for same-organisation moves.
     *
     * @return array<int>
     */
    public function resolve(Team $team, ?Team $newParent): array
    {
        $organisation = $this->organisationFinder->findOrganisation($team);

        return $organisation instanceof Team ? $this->getOrganisationAdmins($organisation) : [];
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
