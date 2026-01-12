<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamHierarchyTraversalService;

it('can determine if team is descendant using collections', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org1 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Org 1'],
    ]);
    $org2 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Org 2'],
    ]);

    $service = new TeamHierarchyTraversalService();

    expect($service->isDescendantOf($org1, $enterprise))->toBeTrue();
    expect($service->isDescendantOf($org2, $enterprise))->toBeTrue();
    expect($service->isDescendantOf($org2, $org1))->toBeFalse();
});

it('can calculate depth using collections', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $service = new TeamHierarchyTraversalService();

    expect($service->getDepth($enterprise))->toBe(1);
    expect($service->getDepth($org))->toBe(2);
});

it('can get ancestry collection', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $service = new TeamHierarchyTraversalService();
    $ancestry = $service->getAncestry($org);

    expect($ancestry)->toHaveCount(1);
    expect($ancestry->first()->id)->toBe($enterprise->id);
});

it('can get descendants collection', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org1 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Org 1'],
    ]);
    $org2 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Org 2'],
    ]);

    $service = new TeamHierarchyTraversalService();
    $descendants = $service->getDescendants($enterprise);

    expect($descendants)->toHaveCount(2);
    expect($descendants->pluck('id')->toArray())->toContain($org1->id, $org2->id);
});
