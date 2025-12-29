<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;

interface ApproverResolverInterface
{
    /**
     * Resolve the list of approver user IDs for a team move.
     *
     * @return array<int>
     */
    public function resolve(Team $team, ?Team $newParent): array;
}
