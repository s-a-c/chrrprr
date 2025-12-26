<?php

declare(strict_types=1);

use App\Exceptions\CannotDeleteKeyUserException;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;

test('cannot delete the last user who is an executive (key role)', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user = User::factory()->create();

    // Ensure roles exist with is_key = true
    if (!Role::query()->where('name', 'executive')->exists()) {
        Role::create(['name' => 'executive', 'is_key' => true]);
    }

    $org->assignExecutive($user);

    $this->expectException(CannotDeleteKeyUserException::class);
    $user->delete();
});

test('can delete a deputy (key role) if another deputy exists', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Ensure roles exist with is_key = true
    if (!Role::query()->where('name', 'deputy')->exists()) {
        Role::create(['name' => 'deputy', 'is_key' => true]);
    }

    $org->assignDeputy($user1);
    $org->assignDeputy($user2);

    // Should be able to delete one of them as count > 1
    $user1->delete();
    expect(User::query()->find($user1->id))->toBeNull();

    // Now user2 is the last one, should NOT be able to delete
    $this->expectException(CannotDeleteKeyUserException::class);
    $user2->delete();
});

test('can delete a user with a non-key role', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user = User::factory()->create();

    // Create a non-key role
    $roleName = 'employee';
    if (!Role::query()->where('name', $roleName)->exists()) {
        Role::create(['name' => $roleName, 'is_key' => false]);
    }

    setPermissionsTeamId($org->id);
    $user->assignRole($roleName);

    // Should be able to delete even if they are the only one
    $user->delete();
    expect(User::query()->find($user->id))->toBeNull();
});

test('can delete a user after key roles are removed', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user = User::factory()->create();

    if (!Role::query()->where('name', 'executive')->exists()) {
        Role::create(['name' => 'executive', 'is_key' => true]);
    }

    $org->assignExecutive($user);

    // Remove role
    $org->removeExecutive();

    // Should proceed without exception
    $user->delete();

    expect(User::query()->find($user->id))->toBeNull();
});
