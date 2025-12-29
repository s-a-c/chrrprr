<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Actions\Teams\MoveTeam;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for approving/rejecting team move requests.
 */
final readonly class TeamMoveApprovalService
{
    public function __construct(
        private MoveTeam $moveTeamAction,
    ) {}

    /**
     * Approve a team move request using collection-based approval tracking.
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
    }

    /**
     * Check if all approvers have approved using collection intersection.
     */
    private function allApproversHaveApproved(TeamMoveApproval $approval): bool
    {
        $requiredApprovers = collect($approval->required_approvers ?? []);
        $approvedUserIds = collect($approval->approvals ?? [])
            ->pluck('user_id');

        // Use collection intersection to check if all required have approved
        return $requiredApprovers->intersect($approvedUserIds)->count() === $requiredApprovers->count();
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
     * Execute the team move and mark approval as complete.
     */
    private function executeMove(TeamMoveApproval $approval): void
    {
        $team = $approval->team;
        $this->moveTeamAction->handle($team, $approval->to_parent_id);

        $approval->status = 'approved';
        $approval->approved_at = now();
    }
}
