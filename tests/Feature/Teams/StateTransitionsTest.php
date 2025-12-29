<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\States\Team\Active;
use App\States\Team\Archived;
use App\States\Team\Inactive;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

test('it defaults to active state', function (): void {
    $team = Enterprise::factory()->create();

    expect($team->state)->toBeInstanceOf(Active::class);
});

test('it can transition from active to inactive', function (): void {
    $team = Enterprise::factory()->create();

    $team->state->transitionTo(Inactive::class);

    expect($team->refresh()->state)->toBeInstanceOf(Inactive::class);
});

test('it can transition from inactive to active', function (): void {
    $team = Enterprise::factory()->create();
    $team->state->transitionTo(Inactive::class);

    $team->state->transitionTo(Active::class);

    expect($team->refresh()->state)->toBeInstanceOf(Active::class);
});

test('it can transition from active to archived', function (): void {
    $team = Enterprise::factory()->create();

    $team->state->transitionTo(Archived::class);

    expect($team->refresh()->state)->toBeInstanceOf(Archived::class);
});

test('it can transition from inactive to archived', function (): void {
    $team = Enterprise::factory()->create();
    $team->state->transitionTo(Inactive::class);

    $team->state->transitionTo(Archived::class);

    expect($team->refresh()->state)->toBeInstanceOf(Archived::class);
});

test('it cannot transition from archived to active', function (): void {
    $team = Enterprise::factory()->create();
    $team->state->transitionTo(Archived::class);

    expect(fn () => $team->state->transitionTo(Active::class))->toThrow(TransitionNotFound::class);
});
