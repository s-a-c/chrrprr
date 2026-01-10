<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Enums\TeamMode;
use App\Enums\TeamType;
use App\Models\Department;
use App\Models\Discipline;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Group;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Squad;
use App\Models\Unit;
use App\Support\Validation\TeamHierarchy\ParentTypeValidator;
use Illuminate\Validation\ValidationException;

// =========================================================================
// HIERARCHICAL TEAMS - Level-based validation
// =========================================================================

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

it('allows sector under enterprise', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $sector = Sector::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Sector'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($sector);

    expect($sector->parent_id)->toBe($enterprise->id);
});

it('allows organisation under sector', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $sector = Sector::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Sector'],
    ]);
    $org = Organisation::factory()->create([
        'parent_id' => $sector->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($org);

    expect($org->parent_id)->toBe($sector->id);
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

it('allows department directly under enterprise (skip intermediate levels)', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $department = Department::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Department'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($department);

    expect($department->parent_id)->toBe($enterprise->id);
});

it('allows unit under department', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $department = Department::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Department'],
    ]);
    $unit = Unit::factory()->create([
        'parent_id' => $department->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Unit'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($unit);

    expect($unit->parent_id)->toBe($department->id);
});

it('throws exception when organisation has no parent', function (): void {
    $org = new Organisation([
        'name' => ['en' => 'Organisation'],
        'type' => 'organisation',
        'parent_id' => null,
    ]);

    $validator = new ParentTypeValidator();

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Organisation requires a parent team.');
    $validator->validate($org);
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

    // Try to create organisation under division (level 3 under level 5 - invalid)
    $invalidOrg = new Organisation([
        'name' => ['en' => 'Invalid Org'],
        'type' => 'organisation',
        'parent_id' => $division->id,
    ]);

    $validator = new ParentTypeValidator();

    $this->expectException(ValidationException::class);
    $validator->validate($invalidOrg);
});

it('throws exception when child level is lower than parent level', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $department = Department::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Department'],
    ]);

    // Try to create division (level 5) under department (level 6) - invalid
    $invalidDivision = new Division([
        'name' => ['en' => 'Invalid Division'],
        'type' => 'division',
        'parent_id' => $department->id,
    ]);

    $validator = new ParentTypeValidator();

    $this->expectException(ValidationException::class);
    $validator->validate($invalidDivision);
});

// =========================================================================
// CROSS-FUNCTIONAL TEAMS - Floating (no parent required)
// =========================================================================

it('allows project without parent (floating)', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $project = Project::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Project'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($project);

    expect($project->parent_id)->toBeNull();
    expect($project->type->mode())->toBe(TeamMode::CROSS_FUNCTIONAL);
});

it('allows squad without parent (floating)', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $squad = Squad::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Squad'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($squad);

    expect($squad->parent_id)->toBeNull();
});

it('allows discipline without parent (floating)', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $discipline = Discipline::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Discipline'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($discipline);

    expect($discipline->parent_id)->toBeNull();
});

it('allows group without parent (floating)', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $group = Group::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Group'],
    ]);

    $validator = new ParentTypeValidator();
    $validator->validate($group);

    expect($group->parent_id)->toBeNull();
});

it('throws exception when cross-functional team has parent', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $project = new Project([
        'name' => ['en' => 'Project'],
        'type' => 'project',
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    $validator = new ParentTypeValidator();

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Cross-functional teams like Project cannot have a parent.');
    $validator->validate($project);
});

// =========================================================================
// TeamType enum tests
// =========================================================================

it('hierarchical types have level numbers', function (): void {
    expect(TeamType::ENTERPRISE->level())->toBe(1);
    expect(TeamType::SECTOR->level())->toBe(2);
    expect(TeamType::ORGANISATION->level())->toBe(3);
    expect(TeamType::BUSINESS_UNIT->level())->toBe(4);
    expect(TeamType::DIVISION->level())->toBe(5);
    expect(TeamType::DEPARTMENT->level())->toBe(6);
    expect(TeamType::UNIT->level())->toBe(7);
});

it('cross-functional types have no level', function (): void {
    expect(TeamType::PROJECT->level())->toBeNull();
    expect(TeamType::SQUAD->level())->toBeNull();
    expect(TeamType::DISCIPLINE->level())->toBeNull();
    expect(TeamType::GROUP->level())->toBeNull();
});

it('all types report correct mode', function (): void {
    // Hierarchical
    expect(TeamType::ENTERPRISE->isHierarchical())->toBeTrue();
    expect(TeamType::SECTOR->isHierarchical())->toBeTrue();
    expect(TeamType::ORGANISATION->isHierarchical())->toBeTrue();
    expect(TeamType::DEPARTMENT->isHierarchical())->toBeTrue();

    // Cross-functional
    expect(TeamType::PROJECT->isFloating())->toBeTrue();
    expect(TeamType::SQUAD->isFloating())->toBeTrue();
    expect(TeamType::DISCIPLINE->isFloating())->toBeTrue();
    expect(TeamType::GROUP->isFloating())->toBeTrue();
});
