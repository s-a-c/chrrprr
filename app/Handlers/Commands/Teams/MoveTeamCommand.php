<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Models\Team;

/**
 * Move Team Command.
 *
 * DTO for the MoveTeam command.
 */
readonly class MoveTeamCommand
{
    /**
     * @param  Team  $team  The team to move
     * @param  int|null  $newParentId  The new parent ID, or null to make it root
     */
    public function __construct(
        public Team $team,
        public ?int $newParentId
    ) {}
}
