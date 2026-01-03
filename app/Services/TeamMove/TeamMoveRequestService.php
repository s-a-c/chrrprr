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

/**
 * Service for requesting team moves.
 */
final readonly class TeamMoveRequestService
{
    public function __construct(
        private ApprovalDecisionEngine $approvalEngine,
        private MoveTeamHandler $moveTeamHandler,
        private ApproverResolverFactory $resolverFactory,
    ) {}

    /**
     * Request a team move, creating an approval request if required.
     *
     * @return Result<TeamMoveApproval|Team, string>
     */
    public function requestMove(
        Team $team,
        ?int $newParentId,
        User $requestedBy,
        ?string $reason = null,
    ): Result {
        $newParent = $newParentId ? Team::query()->withoutGlobalScopes()->find($newParentId) : null;
        $enterprise = $team->tenant;

        // Use decision engine
        if (! $this->approvalEngine->requiresApproval($team, $newParent, $enterprise)) {
            // Move doesn't require approval - execute immediately and return handler's Result
            $moveCommand = new MoveTeamCommand($team, $newParentId);
            $moveResult = $this->moveTeamHandler->handle($moveCommand);

            // Return the handler's Result directly (it's already Result<Team>)
            return $moveResult->flatMap(static function (Team $movedTeam): Result {
                $fresh = $movedTeam->fresh();
                if (! $fresh instanceof Team) {
                    return Result::failure('Team not found after move', ['Team move completed but refresh failed']);
                }

                return Result::success($fresh, ['Team moved successfully']);
            });
        }

        // Approval required - create approval request in transaction
        return Result::try(
            fn (): TeamMoveApproval => DB::transaction(function () use (
                $team,
                $newParentId,
                $requestedBy,
                $reason,
                $newParent,
            ): TeamMoveApproval {
                $requiredApprovers = $this->determineRequiredApprovers($team, $newParent);

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
            }),
            ['Approval request created']
        );
    }

    /**
     * @return int[]
     *
     * @psalm-return array<int>
     */
    private function determineRequiredApprovers(Team $team, ?Team $newParent): array
    {
        $resolver = $this->resolverFactory->getResolver($team, $newParent);

        return $resolver->resolve($team, $newParent);
    }
}
