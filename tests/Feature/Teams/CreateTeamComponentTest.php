<?php

declare(strict_types=1);

use App\Enums\TeamType;
use App\Models\Enterprise;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->enterprise = Enterprise::factory()->create();
});

it('renders the create team page', function (): void {
    $this->actingAs($this->user)
        ->get(route('teams.create'))
        ->assertOk()
        ->assertSeeLivewire('pages::teams.create');
});

it('validates required fields', function (): void {
    Livewire::actingAs($this->user)
        ->test('pages::teams.create')
        ->set('name', '')
        ->set('type', '')
        ->set('parent_id', '')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'type' => 'required',
        ]);
});

it('can create a team', function (): void {
    Livewire::actingAs($this->user)
        ->test('pages::teams.create')
        ->set('name', 'New Organization')
        ->set('type', TeamType::ORGANISATION->value)
        ->set('parent_id', $this->enterprise->id)
        ->set('bio', 'An interesting bio with **markdown**.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('teams.index'));

    $this->assertDatabaseHas('teams', [
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $this->enterprise->id,
    ]);
});
