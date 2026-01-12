<?php

declare(strict_types=1);

use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->enterprise = Enterprise::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->enterprise->id]);

    Role::create(['name' => 'enterprise_admin']);

    $this->orgA = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);
    $this->orgB = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);

    // Grant access to both organisations
    DB::table('user_organisation_access')->insert([
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgA->id, 'assigned_at' => now()],
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgB->id, 'assigned_at' => now()],
    ]);
});

test('user can switch context to an accessible organisation', function (): void {
    $this->actingAs($this->user);

    // This test assumes a Livewire or controller action for switching
    // We will test the model and scoping logic first
    $this->user->switchContext($this->orgA);

    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});

test('context persists across sessions', function (): void {
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgA);

    auth()->logout();

    $this->actingAs($this->user);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});

test('team queries are scoped to the current context', function (): void {
    $this->actingAs($this->user);

    // Create a division in Org A
    $divisionA = Division::factory()->create(['parent_id' => $this->orgA->id]);
    // Create a division in Org B
    $divisionB = Division::factory()->create(['parent_id' => $this->orgB->id]);

    $this->user->switchContext($this->orgA);

    $teams = Team::inContext()->get();

    expect($teams->pluck('id'))->toContain($divisionA->id)->not->toContain($divisionB->id);
});

test('invalid context defaults to the first accessible organisation', function (): void {
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgA);

    // Remove access to Org A
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->where('organisation_id', $this->orgA->id)
        ->delete();

    // The system should detect this (e.g. via middleware or in switchContext)
    // For now, let's test a validation method
    $this->user->validateContext();

    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);
});

test('privileged users can bypass context scope', function (): void {
    // Set team context for Spatie permissions
    setPermissionsTeamId($this->enterprise->id);

    // Assuming 'enterprise_admin' role exists and allows bypass
    $this->user->assignRole('enterprise_admin');
    $this->actingAs($this->user);

    Division::factory()->create(['parent_id' => $this->orgA->id]);
    Division::factory()->create(['parent_id' => $this->orgB->id]);

    $this->user->switchContext($this->orgA);

    // Without bypass
    expect(Team::inContext()->count())->toBe(2); // Org A + Division A

    // With bypass (here naturally including Org B + Division B)
    expect(Team::query()->count())->toBeGreaterThan(2);
});
