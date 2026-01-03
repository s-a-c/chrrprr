<?php

declare(strict_types=1);

namespace App\Handlers\Queries\Teams;

/**
 * Get Team Query.
 *
 * DTO for querying a team by ID or ULID.
 */
readonly class GetTeamQuery
{
    /**
     * @param  int|string|null  $id  Team ID (integer) or ULID (string)
     */
    public function __construct(
        public int|string|null $id = null
    ) {}
}
