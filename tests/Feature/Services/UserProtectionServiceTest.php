<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Services\UserProtectionService;

test('user with no key roles is not protectable', function (): void {
    $user = User::factory()->create();
    $service = new UserProtectionService();

    expect($service->isProtectable($user))->toBeFalse();
});

test('user with key role is not protectable when multiple users have same role', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $role = Role::query()->firstOrCreate(['name' => 'deputy', 'guard_name' => 'web']);
    $role->is_key = true;
    $role->save();

    // Assign both users as deputies
    $org->assignDeputy($user1);
    $org->assignDeputy($user2);

    $service = new UserProtectionService();
    $user1->refresh();
    $user2->refresh();

    // Users are NOT protectable when multiple users hold the same key role
    // Protection only applies when count <= 1 (they're the only one)
    // With 2 deputies, count=2, so count > 1, therefore not protectable
    expect($service->isProtectable($user1))->toBeFalse();
    expect($service->isProtectable($user2))->toBeFalse();
});

test('user with key role as only user is protectable', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user = User::factory()->create();

    $role = Role::query()->firstOrCreate(['name' => 'executive', 'guard_name' => 'web']);
    $role->is_key = true;
    $role->save();

    $org->assignExecutive($user);
    $user->refresh();

    $service = new UserProtectionService();

    expect($service->isProtectable($user))->toBeTrue();
});

test('user with non-key role is not protectable', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user = User::factory()->create();

    Role::query()->firstOrCreate(['name' => 'employee', 'guard_name' => 'web'], ['is_key' => false]);

    setPermissionsTeamId($org->id);
    $user->assignRole('employee');
    $user->refresh();

    $service = new UserProtectionService();

    expect($service->isProtectable($user))->toBeFalse();
});
