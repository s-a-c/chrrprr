<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Teams\MoveTeam;
use App\Enums\TeamType;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use App\Services\TeamMove\ApprovalRuleInterface;
use App\Services\TeamMove\ApproverResolverInterface;
use App\Services\TeamMove\CrossOrganisationApproverResolver;
use App\Services\TeamMove\CrossOrganisationRule;
use App\Services\TeamMove\DepthChangeRule;
use App\Services\TeamMove\DescendantCountRule;
use App\Services\TeamMove\SameOrganisationApproverResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class TeamMoveService
{
    public function __construct(
        private MoveTeam $moveTeamAction,
    ) {}

    /**
     * Request a team move, creating an approval request if required.
     *
     * @return TeamMoveApproval|Team Returns approval request if approval needed, or the moved team if not
     */
    public function requestMove(
        Team $team,
        ?int $newParentId,
        User $requestedBy,
        ?string $reason = null,
    ): TeamMoveApproval|Team {
        return DB::transaction(function () use (
            $team,
            $newParentId,
            $requestedBy,
            $reason,
        ): TeamMoveApproval|Team|null {
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

            // TODO(@system): Dispatch notification event to required approvers

            return TeamMoveApproval::query()->create([
                'team_id' => $team->id,
                'from_parent_id' => $team->parent_id,
                'to_parent_id' => $newParentId,
                'requested_by_id' => $requestedBy->id,
                'status' => 'pending',
                'reason' => $reason,
                'required_approvers' => $requiredApprovers,
                'approvals' => [],
            ]);
        });
    }

    /**
     * Approve a team move request.
     */
    public function approve(TeamMoveApproval $approval, User $approver): void
    {
        $this->validateApprovalRequest($approval, $approver);

        DB::transaction(function () use ($approval, $approver): void {
            $this->recordApproval($approval, $approver);

            if ($this->allApproversHaveApproved($approval)) {
                $this->executeMove($approval);
            }

            $approval->save();
        });
    }

    /**
     * Reject a team move request.
     */
    public function reject(TeamMoveApproval $approval, User $rejector, string $reason): void
    {
        $this->validateRejectionRequest($approval, $rejector);

        $approval->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by_id' => $rejector->id,
            'rejection_reason' => $reason,
        ]);

        // TODO(@system): Dispatch notification event
    }

    /**
     * Validate that the approval request can be approved.
     */
    private function validateApprovalRequest(TeamMoveApproval $approval, User $approver): void
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
    }

    /**
     * Record an approval from a user.
     */
    private function recordApproval(TeamMoveApproval $approval, User $approver): void
    {
        $existingApprovals = $approval->approvals ?? [];
        $existingApprovals[] = [
            'user_id' => $approver->id,
            'approved_at' => now()->toISOString(),
            'status' => 'approved',
        ];

        $approval->approvals = $existingApprovals;
    }

    /**
     * Check if all required approvers have approved.
     */
    private function allApproversHaveApproved(TeamMoveApproval $approval): bool
    {
        $requiredApprovers = $approval->required_approvers ?? [];
        $existingApprovals = $approval->approvals ?? [];
        $approvedUserIds = array_column($existingApprovals, 'user_id');

        return count(array_intersect($requiredApprovers, $approvedUserIds)) === count($requiredApprovers);
    }

    /**
     * Execute the team move and mark approval as complete.
     */
    private function executeMove(TeamMoveApproval $approval): void
    {
        $team = $approval->team;
        $this->moveTeamAction->handle($team, $approval->to_parent_id);

        $approval->status = 'approved';
        $approval->approved_at = now();
    }

    /**
     * Validate that the rejection request can be rejected.
     */
    private function validateRejectionRequest(TeamMoveApproval $approval, User $rejector): void
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
    }

    /**
     * Determine if a team move requires approval based on configurable thresholds.
     */
    private function requiresApproval(Team $team, ?Team $newParent, ?Team $enterprise): bool
    {
        if (! $enterprise instanceof Team) {
            return false;
        }

        $rules = $this->getApprovalRules();

        foreach ($rules as $rule) {
            if (! $rule->requiresApproval($team, $newParent, $enterprise)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Get all approval rules to check.
     *
     * @return array<ApprovalRuleInterface>
     */
    private function getApprovalRules(): array
    {
        return [
            new DescendantCountRule(),
            new DepthChangeRule(),
            new CrossOrganisationRule(),
        ];
    }

    /**
     * Determine required approvers based on move type.
     *
     * @return array<int> Array of user IDs who must approve
     */
    private function determineRequiredApprovers(Team $team, ?Team $newParent): array
    {
        $resolver = $this->getApproverResolver($team, $newParent);

        return $resolver->resolve($team, $newParent);
    }

    /**
     * Get the appropriate approver resolver based on move type.
     */
    private function getApproverResolver(Team $team, ?Team $newParent): ApproverResolverInterface
    {
        if ($this->isCrossOrganisationMove($team, $newParent)) {
            return new CrossOrganisationApproverResolver();
        }

        return new SameOrganisationApproverResolver();
    }

    /**
     * Check if a move is cross-organisation.
     */
    private function isCrossOrganisationMove(Team $team, ?Team $newParent): bool
    {
        $sourceOrg = $this->findOrganisation($team);
        $targetOrg = $newParent instanceof Team ? $this->findOrganisation($newParent) : null;

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
}
