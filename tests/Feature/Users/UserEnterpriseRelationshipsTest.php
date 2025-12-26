<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\User;

test('user belongs to enterprise tenant', function (): void {
    $enterprise = Enterprise::factory()->create();
    $user = User::factory()->create(['tenant_id' => $enterprise->id]);

    expect($user->tenant)->toBeInstanceOf(Enterprise::class)
        ->and($user->tenant->id)->toBe($enterprise->id);
});

test('user tenant relationship returns correct enterprise', function (): void {
    $enterprise = Enterprise::factory()->create();
    $user = User::factory()->create(['tenant_id' => $enterprise->id]);

    $tenant = $user->tenant;

    expect($tenant)->not->toBeNull()
        ->and($tenant->id)->toBe($enterprise->id)
        ->and($tenant->type->value)->toBe('enterprise');
});

test('user can have null tenant', function (): void {
    $user = User::factory()->create(['tenant_id' => null]);

    expect($user->tenant)->toBeNull();
});
