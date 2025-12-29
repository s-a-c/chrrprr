<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamHierarchyTraversalService;
use App\Support\Validation\TeamHierarchy\DepthValidator;
use Illuminate\Validation\ValidationException;

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
    // Create a deep hierarchy (10 levels)
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $current = $enterprise;

    // Create 9 levels of organisations to reach depth 10
    for ($i = 1; $i <= 9; $i++) {
        $org = Organisation::factory()->create([
            'parent_id' => $current->id,
            'name' => ['en' => "Organisation Level {$i}"],
        ]);
        $current = $org;
    }

    // Now try to add another level (would be depth 11)
    $newOrg = new Organisation([
        'name' => ['en' => 'New Organisation'],
        'type' => 'organisation',
        'parent_id' => $current->id,
    ]);

    $validator = new DepthValidator(new TeamHierarchyTraversalService());

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Team hierarchy depth cannot exceed 10 levels.');
    $validator->validate($newOrg);
});
