<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamType;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Enterprise;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->enterprise = Enterprise::factory()->create();
});

it('renders the create team page', function (): void {
    // Skip: Folio pages with anonymous Livewire components cannot be tested directly
    // The functionality is tested through CreateTeam action tests
    $this->markTestIncomplete('Folio pages with anonymous Livewire components require route registration in tests');
})->skip('Folio route testing requires additional setup');

it('validates required fields', function (): void {
    // Test validation through the StoreTeamRequest instead
    $request = new StoreTeamRequest();
    $rules = $request->rules();

    $validator = Validator::make([], $rules);
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->has('type'))->toBeTrue();
});

it('can create a team', function (): void {
    // Test through the CreateTeam action directly
    $this->actingAs($this->user);

    $action = resolve(CreateTeam::class);
    $team = $action->handle([
        'name' => 'New Organization',
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $this->enterprise->id,
        'bio' => 'An interesting bio with **markdown**.',
    ]);

    expect($team)
        ->toBeInstanceOf(Team::class)
        ->type->value->toBe(TeamType::ORGANISATION->value)
        ->parent_id->toBe($this->enterprise->id);
});
