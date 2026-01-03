<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use App\Services\TeamMove\TeamMoveApprovalService;
use App\Services\TeamMove\TeamMoveRequestService;
use Deprecated;
use RuntimeException;

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
     * @return TeamMoveApproval|Team Returns approval request if approval needed, or the moved team if not
     */
    #[Deprecated(message: 'Use TeamMoveRequestService::requestMove() instead')]
    public function requestMove(
        Team $team,
        ?int $newParentId,
        User $requestedBy,
        ?string $reason = null,
    ): TeamMoveApproval|Team {
        $result = $this->requestService->requestMove($team, $newParentId, $requestedBy, $reason);

        return $result->match(
            onSuccess: static fn (TeamMoveApproval|Team $value): TeamMoveApproval|Team => $value,
            onFailure: static fn (string $error): TeamMoveApproval|Team => throw new RuntimeException($error)
        );
    }

    /**
     * Approve a team move request.
     */
    #[Deprecated(message: 'Use TeamMoveApprovalService::approve() instead')]
    public function approve(TeamMoveApproval $approval, User $approver): void
    {
        $result = $this->approvalService->approve($approval, $approver);

        $result->match(
            onSuccess: static function (): void {
                // Success - no action needed
            },
            onFailure: static function (string $error): never {
                throw new RuntimeException($error);
            }
        );
    }

    /**
     * Reject a team move request.
     */
    #[Deprecated(message: 'Use TeamMoveApprovalService::reject() instead')]
    public function reject(TeamMoveApproval $approval, User $rejector, string $reason): void
    {
        $this->approvalService->reject($approval, $rejector, $reason);
    }
}
