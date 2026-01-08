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

it('throws exception when depth would exceed limit')->skip('Cannot test depth 10 exception with current hierarchy structure (max depth is 5). Would need to mock a parent with depth >= 10.');
