<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamHierarchyTraversalService;
use App\Support\Validation\TeamHierarchy\CycleValidator;
use Illuminate\Validation\ValidationException;

it('allows valid hierarchy without cycles', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $validator = new CycleValidator(new TeamHierarchyTraversalService());
    $validator->validate($org);

    expect($org->parent_id)->toBe($enterprise->id);
});

it('throws exception when team is its own parent', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $enterprise->parent_id = $enterprise->id;

    $validator = new CycleValidator(new TeamHierarchyTraversalService());

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('A team cannot be its own parent.');
    $validator->validate($enterprise);
});

it('throws exception when moving would create cycle', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    // Try to move enterprise to be under its own descendant (org)
    $enterprise->parent_id = $org->id;

    $validator = new CycleValidator(new TeamHierarchyTraversalService());

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('A team cannot be moved into its own descendant (would create a cycle).');
    $validator->validate($enterprise);
});

it('allows new team without id', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $newOrg = new Organisation([
        'name' => ['en' => 'New Organisation'],
        'type' => 'organisation',
        'parent_id' => $enterprise->id,
    ]);

    $validator = new CycleValidator(new TeamHierarchyTraversalService());
    $validator->validate($newOrg);

    expect($newOrg->parent_id)->toBe($enterprise->id);
});
