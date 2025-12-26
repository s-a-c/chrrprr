<?php

declare(strict_types=1);

use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;

test('can create division under organisation', function (): void {
    $enterprise = Enterprise::factory()->create();
    $organisation = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    $division = Division::factory()->create([
        'parent_id' => $organisation->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($division)->toBeInstanceOf(Division::class)->parent_id->toBe($organisation->id);
});
