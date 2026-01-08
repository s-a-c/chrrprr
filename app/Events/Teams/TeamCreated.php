<?php

declare(strict_types=1);

namespace App\Events\Teams;

use App\Enums\TeamType;
use Thunk\Verbs\Event;

/**
 * Team Created Event.
 *
 * Fired when a team is created. This event provides permanent audit trail
 * storage via Verbs, while Result monad handles control flow validation.
 */
final class TeamCreated extends Event
{
    public TeamType $type;

    public string $name;

    public ?string $bio = null;

    public ?int $parent_id = null;

    public ?int $tenant_id = null;

    public ?string $state = null;

    public ?string $status = null;
}
