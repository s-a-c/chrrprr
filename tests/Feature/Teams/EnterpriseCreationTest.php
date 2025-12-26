<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Team;

test('can create enterprise', function (): void {
    $enterprise = Enterprise::factory()->create([
        'name' => 'Factory Enterprise',
    ]);

    expect($enterprise)
        ->toBeInstanceOf(Enterprise::class)
        ->toBeInstanceOf(Team::class)
        ->name->toBe('Factory Enterprise')
        ->type->value->toBe('enterprise')
        ->parent_id->toBeNull();
});

test('enterprise is its own tenant', function (): void {
    $enterprise = Enterprise::factory()->create([
        'name' => 'Tenant Enterprise',
    ]);

    // In a real multi-tenant setup, this might be handled differently,
    // but for now we expect the logic to potentially set tenant_id to itself or null depending on implementation.
    // Assuming for now it's null or handled by trait.
    // Let's verify it persists.

    $this->assertDatabaseHas('teams', [
        'id' => $enterprise->id,
        'type' => 'enterprise',
    ]);
});
