<?php

declare(strict_types=1);

use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Reset cached roles and permissions
    app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

    // Setup the "Super Admin" role
    Role::query()->firstOrCreate([
        'name' => 'Super Admin',
        'guard_name' => 'web',
    ], [
        'is_key' => true,
    ]);

    // Create a random permission that we will test against
    Permission::query()->firstOrCreate([
        'name' => 'obliterate database',
        'guard_name' => 'web',
    ]);

    // Register UserPolicy for testing
    Gate::policy(User::class, UserPolicy::class);
});

it('allows super admin to bypass permission checks via gates', function (): void {
    // Arrange: Create a user and assign the Super Admin role (global, team_id = 0)
    $superAdmin = User::factory()->create();
    setPermissionsTeamId(0);
    $superAdmin->assignRole('Super Admin');

    // Act & Assert: Check if they can do the action
    // Note: We never gave them the 'obliterate database' permission explicitly!
    expect($superAdmin->can('obliterate database'))->toBeTrue();
});

it('allows super admin to bypass policy checks', function (): void {
    // Arrange: Create a super admin and a target user
    $superAdmin = User::factory()->create();
    setPermissionsTeamId(0);
    $superAdmin->assignRole('Super Admin');

    $targetUser = User::factory()->create();

    // Act & Assert: Super Admin should be able to perform actions denied by policy
    // UserPolicy::viewAny() returns false, but Super Admin should bypass it
    expect($superAdmin->can('viewAny', User::class))->toBeTrue();
    expect($superAdmin->can('view', $targetUser))->toBeTrue();
    expect($superAdmin->can('create', User::class))->toBeTrue();
    expect($superAdmin->can('update', $targetUser))->toBeTrue();
    expect($superAdmin->can('delete', $targetUser))->toBeTrue();
});

it('does not allow standard users to bypass permission checks', function (): void {
    // Arrange: Create a standard user (no roles)
    $user = User::factory()->create();

    // Act & Assert: They should strictly NOT be able to do this
    expect($user->can('obliterate database'))->toBeFalse();
});

it('does not allow standard users to bypass policy checks', function (): void {
    // Arrange: Create a standard user
    $user = User::factory()->create();
    $targetUser = User::factory()->create();

    // Act & Assert: Standard users should be denied by policies
    expect($user->can('viewAny', User::class))->toBeFalse();
    expect($user->can('view', $targetUser))->toBeFalse();
    expect($user->can('create', User::class))->toBeFalse();
    expect($user->can('update', $targetUser))->toBeFalse();
    expect($user->can('delete', $targetUser))->toBeFalse();
});

it('allows standard users to perform actions if they have specific permission', function (): void {
    // Arrange: Create a user and give them the permission explicitly (global, team_id = 0)
    $editor = User::factory()->create();
    setPermissionsTeamId(0);
    $editor->givePermissionTo('obliterate database');

    // Act & Assert
    expect($editor->can('obliterate database'))->toBeTrue();
});

it('allows system administrator user to bypass all checks', function (): void {
    // Arrange: Create the System Administrator user (simulating migration behavior)
    $systemAdministrator = User::query()->firstOrCreate([
        'email' => 'system@example.com',
    ], [
        'name' => 'System Administrator',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'state' => UserState::ACTIVE,
        'status' => UserStatus::OFFLINE,
    ]);

    // Assign Super Admin role if not already assigned (global, team_id = 0)
    if (! $systemAdministrator->hasRole('Super Admin')) {
        $superAdminRole = Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();
        if ($superAdminRole !== null) {
            setPermissionsTeamId(0);
            $systemAdministrator->assignRole($superAdminRole);
        }
    }

    // Assert: System Administrator should have Super Admin role
    expect($systemAdministrator->hasRole('Super Admin'))->toBeTrue();

    // Assert: System Administrator can bypass permission checks
    expect($systemAdministrator->can('obliterate database'))->toBeTrue();

    // Assert: System Administrator can bypass policy checks
    $targetUser = User::factory()->create();
    expect($systemAdministrator->can('viewAny', User::class))->toBeTrue();
    expect($systemAdministrator->can('view', $targetUser))->toBeTrue();
    expect($systemAdministrator->can('create', User::class))->toBeTrue();
    expect($systemAdministrator->can('update', $targetUser))->toBeTrue();
    expect($systemAdministrator->can('delete', $targetUser))->toBeTrue();
});

it('ensures super admin role is marked as key role', function (): void {
    // Arrange: Get the Super Admin role
    $superAdminRole = Role::query()
        ->where('name', 'Super Admin')
        ->where('guard_name', 'web')
        ->first();

    // Assert: The role should exist and be marked as key
    expect($superAdminRole)->not->toBeNull();
    expect($superAdminRole?->is_key)->toBeTrue();
});
