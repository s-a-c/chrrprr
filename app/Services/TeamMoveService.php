<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use App\Services\TeamMove\TeamMoveApprovalService;
use App\Services\TeamMove\TeamMoveRequestService;

/**
 * @deprecated Use TeamMoveRequestService and TeamMoveApprovalService instead
 */
final readonly class TeamMoveService
{
    public function __construct(
        private TeamMoveRequestService $requestService,
        private TeamMoveApprovalService $approvalService,
    ) {}

    /**
     * Request a team move, creating an approval request if required.
     *
     * @deprecated Use TeamMoveRequestService::requestMove() instead
     *
     * @return TeamMoveApproval|Team Returns approval request if approval needed, or the moved team if not
     */
    public function requestMove(
        Team $team,
        ?int $newParentId,
        User $requestedBy,
        ?string $reason = null,
    ): TeamMoveApproval|Team {
        return $this->requestService->requestMove($team, $newParentId, $requestedBy, $reason);
    }

    /**
     * Approve a team move request.
     *
     * @deprecated Use TeamMoveApprovalService::approve() instead
     */
    public function approve(TeamMoveApproval $approval, User $approver): void
    {
        $this->approvalService->approve($approval, $approver);
    }

    /**
     * Reject a team move request.
     *
     * @deprecated Use TeamMoveApprovalService::reject() instead
     */
    public function reject(TeamMoveApproval $approval, User $rejector, string $reason): void
    {
        $this->approvalService->reject($approval, $rejector, $reason);
    }
}
