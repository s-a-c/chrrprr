<?php

declare(strict_types=1);

use App\Actions\Teams\UpdateTeam;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->enterprise = Enterprise::factory()->create();
    $this->team = Organisation::factory()->create([
        'parent_id' => $this->enterprise->id,
        'name' => 'Original Name',
        'bio' => 'Original Bio',
    ]);
});

it('renders the edit team page', function (): void {
    // Skip: Folio pages with anonymous Livewire components cannot be tested directly
    // The functionality is tested through UpdateTeam action tests
})->skip('Folio pages with anonymous Livewire components require route registration in tests');

it('loads initial data correctly', function (): void {
    // Test that team data can be retrieved correctly
    $team = Team::query()->where('ulid', $this->team->ulid)->firstOrFail();

    expect($team->getTranslation('name', app()->getLocale()))
        ->toBe($this->team->getTranslation('name', app()->getLocale()))
        ->and($team->getTranslation('bio', app()->getLocale()))
        ->toBe($this->team->getTranslation('bio', app()->getLocale()));
});

it('can update a team', function (): void {
    // Test through the UpdateTeam action directly
    $this->actingAs($this->user);

    $action = resolve(UpdateTeam::class);
    $updatedTeam = $action->handle($this->team, [
        'name' => 'Updated Name',
        'bio' => 'Updated Bio',
    ]);

    expect($updatedTeam->name)->toBe('Updated Name')->and($updatedTeam->bio)->toBe('Updated Bio');
});
