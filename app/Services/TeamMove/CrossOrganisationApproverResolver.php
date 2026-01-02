<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamOrganisationFinderService;
use Override;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

final readonly class CrossOrganisationApproverResolver implements ApproverResolverInterface
{
    public function __construct(
        private TeamOrganisationFinderService $organisationFinder,
    ) {}

    /**
     * Resolve approvers for cross-organisation moves.
     *
     * Uses collection pipeline for functional approach.
     *
     *
     * @psalm-return array<int, never>
     */
    #[Override]
    public function resolve(Team $team, ?Team $newParent): array
    {
        $sourceOrg = $this->organisationFinder->findOrganisation($team);
        $targetOrg = $newParent instanceof Team
            ? $this->organisationFinder->findOrganisation($newParent)
            : null;

        $approvers = collect();

        if ($sourceOrg instanceof Team) {
            $approvers = $approvers->merge($this->getOrganisationAdmins($sourceOrg));
        }

        if ($targetOrg && $targetOrg->id !== $sourceOrg?->id) {
            $approvers = $approvers->merge($this->getOrganisationAdmins($targetOrg));
        }

        return $approvers->unique()->values()->all();
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
