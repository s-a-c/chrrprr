<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Models\Team;

/**
 * Update Team Command.
 *
 * DTO for the UpdateTeam command.
 */
readonly class UpdateTeamCommand
{
    /**
     * @param  Team  $team  The team to update
     * @param  array<string, mixed>  $data  The update data
     */
    public function __construct(
        public Team $team,
        public array $data
    ) {}
}
