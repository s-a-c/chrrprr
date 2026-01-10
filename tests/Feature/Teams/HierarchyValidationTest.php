<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Squad;
use App\Models\Unit;
use Illuminate\Validation\ValidationException;

// =========================================================================
// HIERARCHICAL TEAMS - Level-based validation
// =========================================================================

test('organisation cannot belong to division', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org->id,
    ]);

    // Organisation (level 3) cannot be under Division (level 5)
    expect(fn () => Organisation::query()->create([
        'name' => 'Invalid Organisation',
        'type' => 'organisation',
        'parent_id' => $division->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

test('department can belong to enterprise (flexible hierarchy)', function (): void {
    $enterprise = Enterprise::factory()->create();

    // Department (level 6) CAN be directly under Enterprise (level 1)
    // Level-based validation: child > parent, so 6 > 1 is valid
    $department = Department::query()->create([
        'name' => ['en' => 'Department'],
        'type' => 'department',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($department)->toBeInstanceOf(Department::class);
    expect($department->parent_id)->toBe($enterprise->id);
});

test('sector can belong to enterprise', function (): void {
    $enterprise = Enterprise::factory()->create();

    $sector = Sector::query()->create([
        'name' => ['en' => 'Sector'],
        'type' => 'sector',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($sector)->toBeInstanceOf(Sector::class);
    expect($sector->parent_id)->toBe($enterprise->id);
});

test('unit can belong to department', function (): void {
    $enterprise = Enterprise::factory()->create();
    $department = Department::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    $unit = Unit::query()->create([
        'name' => ['en' => 'Unit'],
        'type' => 'unit',
        'parent_id' => $department->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($unit)->toBeInstanceOf(Unit::class);
    expect($unit->parent_id)->toBe($department->id);
});

test('division cannot belong to department (wrong level order)', function (): void {
    $enterprise = Enterprise::factory()->create();
    $department = Department::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    // Division (level 5) cannot be under Department (level 6)
    expect(fn () => Division::query()->create([
        'name' => 'Invalid Division',
        'type' => 'division',
        'parent_id' => $department->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

test('enterprise cannot have a parent', function (): void {
    $enterprise = Enterprise::factory()->create();

    expect(fn () => Enterprise::query()->create([
        'name' => 'Child Enterprise',
        'type' => 'enterprise',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

// =========================================================================
// CROSS-FUNCTIONAL TEAMS - Floating (no parent)
// =========================================================================

test('project must be floating (no parent)', function (): void {
    $enterprise = Enterprise::factory()->create();

    // Projects are cross-functional - they cannot have a parent
    expect(fn () => Project::query()->create([
        'name' => 'Invalid Project',
        'type' => 'project',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

test('project can be created without parent', function (): void {
    $enterprise = Enterprise::factory()->create();

    $project = Project::query()->create([
        'name' => ['en' => 'Floating Project'],
        'type' => 'project',
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
    ]);

    expect($project)->toBeInstanceOf(Project::class);
    expect($project->parent_id)->toBeNull();
});

test('squad must be floating (no parent)', function (): void {
    $enterprise = Enterprise::factory()->create();

    expect(fn () => Squad::query()->create([
        'name' => 'Invalid Squad',
        'type' => 'squad',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

test('squad can be created without parent', function (): void {
    $enterprise = Enterprise::factory()->create();

    $squad = Squad::query()->create([
        'name' => ['en' => 'Floating Squad'],
        'type' => 'squad',
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
    ]);

    expect($squad)->toBeInstanceOf(Squad::class);
    expect($squad->parent_id)->toBeNull();
});
