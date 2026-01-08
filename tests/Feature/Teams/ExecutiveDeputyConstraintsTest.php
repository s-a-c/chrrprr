<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

test('cannot assign multiple executives to a team', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create role with guard_name to match other tests
    Role::query()->firstOrCreate(['name' => 'executive', 'guard_name' => 'web']);

    // Implement method on Team model later
    $org->assignExecutive($user1);

    // Assert user1 has role on this team
    setPermissionsTeamId($org->id);
    expect($user1->hasRole('executive'))->toBeTrue();
    setPermissionsTeamId(null); // Reset

    // Expect exception on second assignment
    $this->expectException(ValidationException::class);
    $org->assignExecutive($user2);
});

test('can assign deputies to a team', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Role::query()->firstOrCreate(['name' => 'deputy', 'guard_name' => 'web']);

    $org->assignDeputy($user1);

    setPermissionsTeamId($org->id);
    expect($user1->hasRole('deputy'))->toBeTrue();
    setPermissionsTeamId(null);

    // Can assign multiple
    $org->assignDeputy($user2);
    setPermissionsTeamId($org->id);
    expect($user2->hasRole('deputy'))->toBeTrue();
    setPermissionsTeamId(null);

    // Check deputies() helper
    expect($org->deputies())->toHaveCount(2);
});

test('can replace executive if explicitly handled')->skip('Replacement logic not yet tested');
