<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

/**
 *final  Create Team Command.
 *
 * DTO for the CreateTeam command.
 */
final readonly class CreateTeamCommand
{
    /**
     * @param  array<string, mixed>  $data  Team creation data
     */
    public function __construct(
        public array $data
    ) {}
}
