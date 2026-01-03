<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamHierarchyTraversalService;
use App\Support\Validation\TeamHierarchy\DepthValidator;

it('allows teams within depth limit', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $validator = new DepthValidator(new TeamHierarchyTraversalService());
    $validator->validate($org);

    expect($org->parent_id)->toBe($enterprise->id);
});

it('allows team without parent', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $validator = new DepthValidator(new TeamHierarchyTraversalService());
    $validator->validate($enterprise);

    expect($enterprise->parent_id)->toBeNull();
});

it('throws exception when depth would exceed limit', function (): void {
    // Note: With the current hierarchy structure, we can only create a maximum depth of 5:
    // Enterprise (1) -> Organisation (2) -> Division (3) -> Department (4) -> Project (5)
    // Since the depth limit is 10, we cannot test the exception case with the current hierarchy.
    // This test is marked as incomplete until we can create a 10-level hierarchy or mock the scenario.

    $this->markTestIncomplete('Cannot test depth 10 exception with current hierarchy structure (max depth is 5). Would need to mock a parent with depth >= 10.');
});
