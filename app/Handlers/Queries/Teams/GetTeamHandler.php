<?php

declare(strict_types=1);

namespace App\Handlers\Queries\Teams;

use App\Contracts\QueryHandler;
use App\Handlers\BaseHandler;
use App\Models\Team;
use App\Support\Result;
use Override;

/**
 * Get Team Query Handler.
 *
 * Handles team retrieval using the monadic CQRS pattern.
 * Uses Option monad behavior: Success = Found, Failure = Not Found (404).
 *
 * Note: This handler operates in tenant context. Queries are automatically
 * scoped to the current tenant via the BelongsToTenant trait. No manual
 * tenant_id filtering is required.
 */
final class GetTeamHandler extends BaseHandler implements QueryHandler
{
    /**
     * Ask the GetTeam query.
     *
     * Note: Queries are automatically scoped to current tenant via BelongsToTenant trait.
     *
     * @param  GetTeamQuery  $query  The query to execute
     * @return Result Success with Team if found, Failure if not found
     */
    #[Override]
    public function ask(object $query): Result
    {
        if (! $query instanceof GetTeamQuery) {
            return Result::failure('Invalid query type', ['Expected GetTeamQuery']);
        }

        if ($query->id === null) {
            return Result::failure('Team ID is required', ['Query validation failed']);
        }

        // Queries are automatically scoped to current tenant via BelongsToTenant trait
        // Determine if ID is ULID (string) or integer ID
        $team = is_string($query->id)
            ? Team::query()->where('ulid', $query->id)->first()
            : Team::query()->find($query->id);

        return $this->ensureFound(
            $team,
            "Team not found: {$query->id}"
        );
    }
}
