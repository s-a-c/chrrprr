<?php

declare(strict_types=1);

namespace App\Events\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Validation\ValidationException;
use Thunk\Verbs\Event;

/**
 * Team Created Event.
 *
 * Fired when a team is created. This event provides permanent audit trail
 * storage via Verbs, while Result monad handles control flow validation.
 */
final class TeamCreated extends Event
{
    public ?int $parent_id = null;

    public ?int $tenant_id = null;

    public ?string $state = null;

    public ?string $status = null;

    /**
     * Create a new TeamCreated event instance.
     *
     * @param  TeamType  $type  The type of team being created
     * @param  array<string, mixed>|string  $name  Team name (translatable array or string)
     * @param  array<string, mixed>|string|null  $bio  Team bio (translatable array or string, optional)
     * @param  TeamCreatedData|null  $data  Optional team data (parent_id, tenant_id, state, status)
     *
     * @psalm-suppress PossiblyUnusedProperty Used in constructor to extract values to individual properties
     */
    public function __construct(
        public TeamType $type,
        public array|string $name,
        public array|string|null $bio = null,
        public ?TeamCreatedData $data = null,
    ) {
        $this->parent_id = $data?->parent_id;
        $this->tenant_id = $data?->tenant_id;
        $this->state = $data?->state;
        $this->status = $data?->status;
    }

    /**
     * Validate that the team can be created given the hierarchy rules.
     *
     * This validation runs before the event is fired. If validation fails,
     * the event will throw EventNotValid, which will be caught by Result::try().
     */
    public function validate(): void
    {
        $parent = $this->parent_id !== null
            ? Team::query()->withoutGlobalScopes()->find($this->parent_id)
            : null;

        try {
            TeamHierarchyValidator::validate($this->type, $parent);
        } catch (ValidationException $e) {
            $messages = $e->errors();
            $firstMessage = collect($messages)->flatten()->first();
            if (! is_string($firstMessage)) {
                $firstMessage = 'Invalid team hierarchy';
            }

            $this->assert(false, $firstMessage);
        }
    }
}
