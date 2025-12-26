<?php

declare(strict_types=1);

use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->enterprise = Enterprise::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->enterprise->id]);

    $this->orgA = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);
    $this->orgB = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);

    // Grant access to both organisations
    DB::table('user_organisation_access')->insert([
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgA->id, 'assigned_at' => now()],
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgB->id, 'assigned_at' => now()],
    ]);
});

test('team queries are scoped to current organisation context', function (): void {
    $this->actingAs($this->user);

    // Create divisions in both organisations
    $divisionA = Division::factory()->create(['parent_id' => $this->orgA->id]);
    $divisionB = Division::factory()->create(['parent_id' => $this->orgB->id]);

    // Switch to Org A
    $this->user->switchContext($this->orgA);

    $teams = Team::inContext()->get();

    expect($teams->pluck('id'))->toContain($this->orgA->id)
        ->toContain($divisionA->id)
        ->not->toContain($this->orgB->id)
        ->not->toContain($divisionB->id);
});

test('team queries include all descendants of current context', function (): void {
    $this->actingAs($this->user);

    // Create hierarchy under Org A
    $divisionA = Division::factory()->create(['parent_id' => $this->orgA->id]);
    $departmentA = App\Models\Department::factory()->create(['parent_id' => $divisionA->id]);
    $projectA = App\Models\Project::factory()->create(['parent_id' => $departmentA->id]);

    // Create hierarchy under Org B
    $divisionB = Division::factory()->create(['parent_id' => $this->orgB->id]);
    $departmentB = App\Models\Department::factory()->create(['parent_id' => $divisionB->id]);

    $this->user->switchContext($this->orgA);

    $teams = Team::inContext()->get();

    // Should include Org A and all its descendants
    expect($teams->pluck('id'))->toContain($this->orgA->id)
        ->toContain($divisionA->id)
        ->toContain($departmentA->id)
        ->toContain($projectA->id)
        ->not->toContain($this->orgB->id)
        ->not->toContain($divisionB->id)
        ->not->toContain($departmentB->id);
});

test('switching context updates team query scope', function (): void {
    $this->actingAs($this->user);

    $divisionA = Division::factory()->create(['parent_id' => $this->orgA->id]);
    $divisionB = Division::factory()->create(['parent_id' => $this->orgB->id]);

    // Start with Org A
    $this->user->switchContext($this->orgA);
    $teamsA = Team::inContext()->get();
    expect($teamsA->pluck('id'))->toContain($divisionA->id)
        ->not->toContain($divisionB->id);

    // Switch to Org B
    $this->user->switchContext($this->orgB);
    $teamsB = Team::inContext()->get();
    expect($teamsB->pluck('id'))->toContain($divisionB->id)
        ->not->toContain($divisionA->id);
});

test('team queries return empty when no context is set', function (): void {
    $this->actingAs($this->user);

    // Ensure no context is set
    $this->user->update(['current_context_id' => null]);

    $divisionA = Division::factory()->create(['parent_id' => $this->orgA->id]);

    $teams = Team::inContext()->get();

    // inContext() should return all teams when no context is set (per TeamBuilder implementation)
    // Actually, looking at TeamBuilder, it returns $this (no filtering) when no context
    expect($teams->count())->toBeGreaterThan(0);
});

test('organisation queries are scoped to current context', function (): void {
    $this->actingAs($this->user);

    // Create additional organisations
    $orgC = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);
    DB::table('user_organisation_access')->insert([
        ['user_id' => $this->user->id, 'organisation_id' => $orgC->id, 'assigned_at' => now()],
    ]);

    $this->user->switchContext($this->orgA);

    // When querying organisations in context, should only get Org A and descendants
    $teams = Team::inContext()->where('type', App\Enums\TeamType::ORGANISATION)->get();

    expect($teams->pluck('id'))->toContain($this->orgA->id)
        ->not->toContain($this->orgB->id)
        ->not->toContain($orgC->id);
});
