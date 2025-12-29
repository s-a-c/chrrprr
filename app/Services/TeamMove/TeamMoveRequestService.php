<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Actions\Teams\MoveTeam;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Service for requesting team moves.
 */
final readonly class TeamMoveRequestService
{
    public function __construct(
        private MoveTeam $moveTeamAction,
        private ApprovalDecisionEngine $approvalEngine,
        private ApproverResolverFactory $resolverFactory,
    ) {}

    /**
     * Request a team move, creating an approval request if required.
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
        ): TeamMoveApproval|Team {
            $newParent = $newParentId ? Team::query()->withoutGlobalScopes()->find($newParentId) : null;
            $enterprise = $team->tenant;

            // Use decision engine
            if (! $this->approvalEngine->requiresApproval($team, $newParent, $enterprise)) {
                $this->moveTeamAction->handle($team, $newParentId);

                return $team->fresh();
            }

            // Create approval request
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
        });
    }

    private function determineRequiredApprovers(Team $team, ?Team $newParent): array
    {
        $resolver = $this->resolverFactory->getResolver($team, $newParent);

        return $resolver->resolve($team, $newParent);
    }
}
