<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;

test('can create department under division', function (): void {
    $enterprise = Enterprise::factory()->create();
    $organisation = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $division = Division::factory()->create(['parent_id' => $organisation->id]);

    $department = Department::factory()->create([
        'parent_id' => $division->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($department)->toBeInstanceOf(Department::class)->parent_id->toBe($division->id);
});
