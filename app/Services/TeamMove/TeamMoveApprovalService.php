<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Handlers\Commands\Teams\MoveTeamCommand;
use App\Handlers\Commands\Teams\MoveTeamHandler;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use App\Support\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Service for approving/rejecting team move requests.
 */
final readonly class TeamMoveApprovalService
{
    public function __construct(
        private MoveTeamHandler $moveTeamHandler,
    ) {}

    /**
     * Approve a team move request using collection-based approval tracking.
     *
     * @return Result<bool, string>
     */
    public function approve(TeamMoveApproval $approval, User $approver): Result
    {
        return Result::try(
            function () use ($approval, $approver): bool {
                $this->validateApprovalRequest($approval, $approver);

                return true;
            },
            ['Validating approval request']
        )->flatMap(fn (): Result => Result::try(
            fn (): bool => DB::transaction(function () use ($approval, $approver): bool {
                $this->recordApproval($approval, $approver);

                // If all approvers have approved, we need to execute the move
                // But executeMove returns Result, and we're in a transaction
                // So we'll call it and unwrap the Result (throwing on failure to rollback transaction)
                if ($this->allApproversHaveApproved($approval)) {
                    $executeResult = $this->executeMove($approval);
                    if ($executeResult->isFailure) {
                        // Throw to rollback transaction - Result::try will catch and convert to failure Result
                        throw new RuntimeException($executeResult->error);
                    }
                }

                $approval->save();

                return true;
            }),
            ['Recording approval and executing move if needed']
        ));
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

        $requiredApprovers = collect($approval->required_approvers ?? []);
        if ($requiredApprovers->doesntContain($approver->id)) {
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

        $requiredApprovers = collect($approval->required_approvers ?? []);
        if ($requiredApprovers->doesntContain($rejector->id)) {
            throw ValidationException::withMessages([
                'rejector' => ['You are not authorized to reject this request.'],
            ]);
        }
    }

    /**
     * Record an approval from a user.
     *
     * Uses collection push for functional approach.
     */
    private function recordApproval(TeamMoveApproval $approval, User $approver): void
    {
        $approval->approvals = collect($approval->approvals ?? [])
            ->push([
                'user_id' => $approver->id,
                'approved_at' => now()->toISOString(),
                'status' => 'approved',
            ])
            ->all();
    }

    /**
     * Execute the team move and mark approval as complete.
     *
     * @return Result<bool, string>
     */
    private function executeMove(TeamMoveApproval $approval): Result
    {
        $team = Team::query()->withoutGlobalScopes()->find($approval->team_id);
        if (! $team instanceof Team) {
            return Result::failure('Team not found for approval', ['Team move execution failed']);
        }

        $moveCommand = new MoveTeamCommand($team, $approval->to_parent_id);
        $moveResult = $this->moveTeamHandler->handle($moveCommand);

        return $moveResult->flatMap(static function () use ($approval): Result {
            $approval->status = 'approved';
            $approval->approved_at = now();

            return Result::success(true, ['Team move executed and approval marked as complete']);
        });
    }
}
