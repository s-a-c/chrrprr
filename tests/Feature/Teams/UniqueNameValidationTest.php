<?php

declare(strict_types=1);

use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use Illuminate\Validation\ValidationException;

test('teams must have unique name within same parent', function (): void {
    $enterprise = Enterprise::factory()->create();
    Organisation::factory()->create([
        'name' => ['en' => 'Unique Org'],
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    $this->expectException(ValidationException::class);

    Organisation::query()->create([
        'name' => ['en' => 'Unique Org'],
        'type' => 'organisation',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);
});

test('teams can have same name in different parents', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org1 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    // Create another organisation to hold the duplicate name
    $org2 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    // Create a division under org1
    Division::factory()->create([
        'name' => ['en' => 'Common Division'],
        'parent_id' => $org1->id,
        'tenant_id' => $enterprise->id,
    ]);

    // Create a division under org2 with same name - SHOULD SUCCEED
    $div2 = Division::query()->create([
        'name' => ['en' => 'Common Division'],
        'type' => 'division',
        'parent_id' => $org2->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($div2)->toBeInstanceOf(Division::class);
});
