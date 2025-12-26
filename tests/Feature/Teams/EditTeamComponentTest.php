<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Livewire\Livewire;

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
    $this->actingAs($this->user)
        ->get(route('teams.edit', ['ulid' => $this->team->ulid]))
        ->assertOk()
        ->assertSeeLivewire('pages::teams.[ulid]');
});

it('loads initial data correctly', function (): void {
    Livewire::actingAs($this->user)
        ->test('pages::teams.[ulid]', ['ulid' => $this->team->ulid])
        ->assertSet('name', $this->team->getTranslation('name', app()->getLocale()))
        ->assertSet('bio', $this->team->getTranslation('bio', app()->getLocale()));
});

it('can update a team', function (): void {
    Livewire::actingAs($this->user)
        ->test('pages::teams.[ulid]', ['ulid' => $this->team->ulid])
        ->set('name', 'Updated Name')
        ->set('bio', 'Updated Bio')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('teams.index'));

    $this->team->refresh();
    expect($this->team->name)->toBe('Updated Name');
    expect($this->team->bio)->toBe('Updated Bio');
});
