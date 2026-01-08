<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Role;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    // Ensure roles exist
    app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::query()->firstOrCreate(['name' => 'executive', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'deputy', 'guard_name' => 'web']);

    Gate::policy(Enterprise::class, TeamPolicy::class);
});

it('allows executive to update team bio', function (): void {
    $user = User::factory()->create();
    $team = Enterprise::factory()->create();

    $team->assignExecutive($user);

    expect($user->can('update', $team))->toBeTrue();
});

it('allows deputy to update team bio', function (): void {
    $user = User::factory()->create();
    $team = Enterprise::factory()->create();

    $team->assignDeputy($user);

    expect($user->can('update', $team))->toBeTrue();
});

it('denies user without role to update team bio', function (): void {
    $user = User::factory()->create();
    $team = Enterprise::factory()->create();

    expect($user->can('update', $team))->toBeFalse();
});

it('denies user with unrelated role to update team bio', function (): void {
    $user = User::factory()->create();
    $team = Enterprise::factory()->create();

    // Create a random role and assign it (assuming we have other roles or create one)
    // For now, just ensuring no-role is false is enough, but let's check cross-team

    $otherTeam = Enterprise::factory()->create();

    $otherTeam->assignExecutive($user);

    expect($user->can('update', $team))->toBeFalse();
});
