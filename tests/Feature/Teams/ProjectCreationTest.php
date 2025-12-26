<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;

test('can create project under department', function (): void {
    $enterprise = Enterprise::factory()->create();
    $organisation = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $division = Division::factory()->create(['parent_id' => $organisation->id]);
    $department = Department::factory()->create(['parent_id' => $division->id]);

    $project = Project::factory()->create([
        'parent_id' => $department->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($project)->toBeInstanceOf(Project::class)->parent_id->toBe($department->id);
});
