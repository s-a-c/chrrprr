<?php

declare(strict_types=1);

use App\Enums\TeamType;
use App\Events\Teams\TeamCreated;
use App\Handlers\Commands\Teams\CreateTeamCommand;
use App\Handlers\Commands\Teams\CreateTeamHandler;
use App\Models\Enterprise;
use App\Models\Team;
use App\Models\User;
use Thunk\Verbs\Models\VerbEvent;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->enterprise = Enterprise::factory()->create();
    $this->handler = resolve(CreateTeamHandler::class);
});

it('creates a team and fires TeamCreated event successfully', function (): void {
    $this->actingAs($this->user);

    $command = new CreateTeamCommand([
        'name' => 'New Organisation',
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $this->enterprise->id,
        'bio' => 'An interesting bio.',
    ]);

    $result = $this->handler->handle($command);

    expect($result->isSuccess)->toBeTrue();

    $team = $result->value;
    expect($team)
        ->toBeInstanceOf(Team::class)
        ->type->value->toBe(TeamType::ORGANISATION->value)
        ->parent_id->toBe($this->enterprise->id);

    // Verify event was persisted to verb_events table
    $this->assertDatabaseHas('verb_events', [
        'type' => TeamCreated::class,
    ]);

    // Verify event data is stored correctly
    $verbEvent = VerbEvent::query()->where('type', TeamCreated::class)->first();
    expect($verbEvent)->not->toBeNull();

    $event = $verbEvent->event();
    expect($event)
        ->toBeInstanceOf(TeamCreated::class)
        ->type->value->toBe(TeamType::ORGANISATION->value)
        ->parent_id->toBe($this->enterprise->id);
});

it('returns Result::failure when event validation fails', function (): void {
    $this->actingAs($this->user);

    // Try to create a PROJECT without a parent (invalid hierarchy)
    $command = new CreateTeamCommand([
        'name' => 'Orphan Project',
        'type' => TeamType::PROJECT->value,
        'parent_id' => null, // Invalid: PROJECT requires parent
    ]);

    $result = $this->handler->handle($command);

    expect($result->isFailure)->toBeTrue();

    expect($result->error)->toContain('parent');

    // Verify event was NOT persisted
    $this->assertDatabaseMissing('verb_events', [
        'type' => TeamCreated::class,
    ]);

    // Verify team was NOT created
    $this->assertDatabaseMissing('teams', [
        'name' => json_encode(['en' => 'Orphan Project']),
    ]);
});

it('accumulates Writer logs alongside Verbs events', function (): void {
    $this->actingAs($this->user);

    $command = new CreateTeamCommand([
        'name' => 'Test Organisation',
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $this->enterprise->id,
    ]);

    $result = $this->handler->handle($command)->logInternal();

    expect($result->isSuccess)->toBeTrue();
    expect($result->logs)->not->toBeEmpty();
    expect($result->logs)->toContain('Starting team creation');

    // Verify both Writer logs (transient) and Verbs events (persistent) exist
    $this->assertDatabaseHas('verb_events', [
        'type' => TeamCreated::class,
    ]);
});

it('validates team hierarchy through event validate method', function (): void {
    $this->actingAs($this->user);

    // Try to create ORGANISATION under another ORGANISATION (invalid: should be under ENTERPRISE)
    $org = Team::factory()->create([
        'type' => TeamType::ORGANISATION,
        'parent_id' => $this->enterprise->id,
    ]);

    $command = new CreateTeamCommand([
        'name' => 'Invalid Organisation',
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $org->id, // Invalid: ORGANISATION cannot be parent of ORGANISATION
    ]);

    $result = $this->handler->handle($command);

    expect($result->isFailure)->toBeTrue();

    // Verify error message contains hierarchy validation
    expect($result->error)->toContain('organisation');
});

it('creates enterprise team without parent', function (): void {
    $this->actingAs($this->user);

    $command = new CreateTeamCommand([
        'name' => 'New Enterprise',
        'type' => TeamType::ENTERPRISE->value,
        'parent_id' => null, // Valid: ENTERPRISE has no parent
    ]);

    $result = $this->handler->handle($command);

    expect($result->isSuccess)->toBeTrue();

    $team = $result->value;
    expect($team->type->value)->toBe(TeamType::ENTERPRISE->value);
    expect($team->parent_id)->toBeNull();

    // Verify event was persisted
    $this->assertDatabaseHas('verb_events', [
        'type' => TeamCreated::class,
    ]);
});
