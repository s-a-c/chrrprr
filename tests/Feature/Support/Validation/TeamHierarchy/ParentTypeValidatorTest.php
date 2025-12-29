<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Support\Validation\TeamHierarchy\ParentTypeValidator;
use Illuminate\Validation\ValidationException;

it('allows organisation under enterprise', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($org);

    expect($org->parent_id)->toBe($enterprise->id);
});

it('throws exception when organisation has wrong parent type', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org->id,
        'name' => ['en' => 'Division'],
    ]);

    // Try to create organisation under division (should fail)
    $invalidOrg = new Organisation([
        'name' => ['en' => 'Invalid Org'],
        'type' => 'organisation',
        'parent_id' => $division->id,
    ]);

    $validator = new ParentTypeValidator();

    $this->expectException(ValidationException::class);
    $validator->validate($invalidOrg);
});

it('throws exception when team requires parent but has none', function (): void {
    $org = new Organisation([
        'name' => ['en' => 'Organisation'],
        'type' => 'organisation',
        'parent_id' => null,
    ]);

    $validator = new ParentTypeValidator();

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('This team type requires a parent team.');
    $validator->validate($org);
});

it('allows department under division', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org->id,
        'name' => ['en' => 'Division'],
    ]);
    $department = Department::factory()->create([
        'parent_id' => $division->id,
        'name' => ['en' => 'Department'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($department);

    expect($department->parent_id)->toBe($division->id);
});

it('allows project under department', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org->id,
        'name' => ['en' => 'Division'],
    ]);
    $department = Department::factory()->create([
        'parent_id' => $division->id,
        'name' => ['en' => 'Department'],
    ]);
    $project = Project::factory()->create([
        'parent_id' => $department->id,
        'name' => ['en' => 'Project'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($project);

    expect($project->parent_id)->toBe($department->id);
});
