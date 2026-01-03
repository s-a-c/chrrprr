<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Models\Team;
use App\Models\User;

/**
 * Assign Executive Command.
 *
 * DTO for the AssignExecutive command.
 */
readonly class AssignExecutiveCommand
{
    /**
     * @param  Team  $team  The team to assign executive to
     * @param  User  $executive  The user to assign as executive
     */
    public function __construct(
        public Team $team,
        public User $executive
    ) {}
}
