<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

test('organisation cannot belong to division', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org->id,
    ]);

    expect(fn () => Organisation::query()->create([
        'name' => 'Invalid Organisation',
        'type' => 'organisation',
        'parent_id' => $division->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

test('department cannot belong to enterprise', function (): void {
    $enterprise = Enterprise::factory()->create();

    expect(fn () => Department::query()->create([
        'name' => 'Invalid Department',
        'type' => 'department',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]))->toThrow(ValidationException::class);
});

test('project must belong to department', function (): void {
    $enterprise = Enterprise::factory()->create();
    $organisation = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    expect(fn () => Project::query()->create([
        'name' => 'Invalid Project',
        'type' => 'project',
        'parent_id' => $organisation->id,
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
