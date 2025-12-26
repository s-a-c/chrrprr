<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Teams\MoveTeam;
use App\Enums\TeamType;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamMoveService
{
    public function __construct(
        private readonly MoveTeam $moveTeamAction,
    ) {}

    /**
     * Request a team move, creating an approval request if required.
     *
     * @return TeamMoveApproval|Team Returns approval request if approval needed, or the moved team if not
     */
    public function requestMove(Team $team, ?int $newParentId, User $requestedBy, ?string $reason = null): TeamMoveApproval|Team
    {
        return DB::transaction(function () use ($team, $newParentId, $requestedBy, $reason): TeamMoveApproval|Team {
            $newParent = $newParentId ? Team::query()->withoutGlobalScopes()->find($newParentId) : null;
            $enterprise = $team->tenant;

            // Check if approval is required
            if (! $this->requiresApproval($team, $newParent, $enterprise)) {
                // No approval needed, execute move directly
                $this->moveTeamAction->handle($team, $newParentId);

                return $team->fresh();
            }

            // Create approval request
            $requiredApprovers = $this->determineRequiredApprovers($team, $newParent);

            $approval = TeamMoveApproval::create([
                'team_id' => $team->id,
                'from_parent_id' => $team->parent_id,
                'to_parent_id' => $newParentId,
                'requested_by_id' => $requestedBy->id,
                'status' => 'pending',
                'reason' => $reason,
                'required_approvers' => $requiredApprovers,
                'approvals' => [],
            ]);

            // TODO: Dispatch notification event to required approvers

            return $approval;
        });
    }

    /**
     * Approve a team move request.
     */
    public function approve(TeamMoveApproval $approval, User $approver): void
    {
        if (! $approval->isPending()) {
            throw ValidationException::withMessages([
                'approval' => ['This approval request is no longer pending.'],
            ]);
        }

        $requiredApprovers = $approval->required_approvers ?? [];
        if (! in_array($approver->id, $requiredApprovers, true)) {
            throw ValidationException::withMessages([
                'approver' => ['You are not authorized to approve this request.'],
            ]);
        }

        DB::transaction(function () use ($approval, $approver): void {
            $requiredApprovers = $approval->required_approvers ?? [];
            $existingApprovals = $approval->approvals ?? [];
            $existingApprovals[] = [
                'user_id' => $approver->id,
                'approved_at' => now()->toISOString(),
                'status' => 'approved',
            ];

            $approval->approvals = $existingApprovals;

            // Check if all required approvers have approved
            $approvedUserIds = array_column($existingApprovals, 'user_id');
            $allApproved = count(array_intersect($requiredApprovers, $approvedUserIds)) === count($requiredApprovers);

            if ($allApproved) {
                // Execute the move
                $team = $approval->team;
                $this->moveTeamAction->handle($team, $approval->to_parent_id);

                $approval->status = 'approved';
                $approval->approved_at = now();
            }

            $approval->save();
        });
    }

    /**
     * Reject a team move request.
     */
    public function reject(TeamMoveApproval $approval, User $rejector, string $reason): void
    {
        if (! $approval->isPending()) {
            throw ValidationException::withMessages([
                'approval' => ['This approval request is no longer pending.'],
            ]);
        }

        $requiredApprovers = $approval->required_approvers ?? [];
        if (! in_array($rejector->id, $requiredApprovers, true)) {
            throw ValidationException::withMessages([
                'rejector' => ['You are not authorized to reject this request.'],
            ]);
        }

        $approval->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by_id' => $rejector->id,
            'rejection_reason' => $reason,
        ]);

        // TODO: Dispatch notification event
    }

    /**
     * Determine if a team move requires approval based on configurable thresholds.
     */
    private function requiresApproval(Team $team, ?Team $newParent, ?Team $enterprise): bool
    {
        if (! $enterprise) {
            return false;
        }

        // Get approval thresholds from enterprise (defaults if not set)
        $descendantThreshold = $enterprise->move_approval_descendant_threshold ?? 10;
        $depthChangeThreshold = $enterprise->move_approval_depth_change_threshold ?? 2;
        $requireCrossOrg = $enterprise->move_approval_require_cross_org ?? true;

        // Check descendant count threshold
        $descendantCount = $this->getDescendantCount($team);
        if ($descendantCount >= $descendantThreshold) {
            return true;
        }

        // Check depth change threshold
        $currentDepth = $team->getDepth();
        $newDepth = $newParent ? ($newParent->getDepth() + 1) : 1;
        $depthChange = abs($newDepth - $currentDepth);
        if ($depthChange >= $depthChangeThreshold) {
            return true;
        }

        // Check cross-organisation move
        if ($requireCrossOrg && $this->isCrossOrganisationMove($team, $newParent)) {
            return true;
        }

        return false;
    }

    /**
     * Determine required approvers based on move type.
     *
     * @return array<int> Array of user IDs who must approve
     */
    private function determineRequiredApprovers(Team $team, ?Team $newParent): array
    {
        $approvers = [];

        if ($this->isCrossOrganisationMove($team, $newParent)) {
            // Cross-organisation move: require approval from both source and target org admins
            $sourceOrg = $this->findOrganisation($team);
            $targetOrg = $newParent ? $this->findOrganisation($newParent) : null;

            if ($sourceOrg) {
                $sourceAdmins = $this->getOrganisationAdmins($sourceOrg);
                $approvers = array_merge($approvers, $sourceAdmins);
            }

            if ($targetOrg && $targetOrg->id !== ($sourceOrg?->id)) {
                $targetAdmins = $this->getOrganisationAdmins($targetOrg);
                $approvers = array_merge($approvers, $targetAdmins);
            }
        } else {
            // Within same organisation: require approval from organisation admin
            $organisation = $this->findOrganisation($team);
            if ($organisation) {
                $approvers = $this->getOrganisationAdmins($organisation);
            }
        }

        return array_unique($approvers);
    }

    /**
     * Check if a move is cross-organisation.
     */
    private function isCrossOrganisationMove(Team $team, ?Team $newParent): bool
    {
        $sourceOrg = $this->findOrganisation($team);
        $targetOrg = $newParent ? $this->findOrganisation($newParent) : null;

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

    /**
     * Get organisation admin user IDs.
     *
     * @return array<int>
     */
    private function getOrganisationAdmins(Team $organisation): array
    {
        // Find users with 'organisation_admin' role scoped to this organisation
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($organisation->id);

        try {
            // Check if role exists first
            try {
                return User::query()
                    ->role('organisation_admin')
                    ->pluck('id')
                    ->toArray();
            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
                // Role doesn't exist yet, return empty array
                return [];
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    /**
     * Get the count of all descendants (recursive).
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
