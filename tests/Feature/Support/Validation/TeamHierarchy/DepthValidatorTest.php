<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Models\Division;
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

it('throws exception when depth exceeds hard limit', function (): void {
    // Set hard limit to 2 (Enterprise = depth 1, Organisation = depth 2)
    config(['teams.hierarchy.hard_depth_limit' => 2]);

    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    // Try to create Division under Organisation (would be depth 3, exceeds hard limit of 2)
    $division = Division::factory()->make([
        'parent_id' => $org->id,
        'name' => ['en' => 'Division'],
    ]);

    $validator = new DepthValidator(new TeamHierarchyTraversalService());

    expect(fn () => $validator->validate($division))
        ->toThrow(ValidationException::class, 'cannot exceed 2 levels');
});

it('throws exception when depth exceeds tenant soft limit', function (): void {
    config(['teams.hierarchy.hard_depth_limit' => 10]);

    // Set tenant soft limit to 1 (only Enterprise allowed, no children)
    $enterprise = Enterprise::factory()->create(['depth_limit' => 1]);

    // Try to create Organisation under Enterprise (would be depth 2, exceeds soft limit of 1)
    $org = Organisation::factory()->make([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $validator = new DepthValidator(new TeamHierarchyTraversalService());

    expect(fn () => $validator->validate($org))
        ->toThrow(ValidationException::class, 'exceeds tenant limit');
});

it('allows depth within both limits', function (): void {
    config(['teams.hierarchy.hard_depth_limit' => 10, 'teams.hierarchy.default_soft_depth_limit' => 5]);

    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    // Division would be depth 3, well within both limits
    $division = Division::factory()->make([
        'parent_id' => $org->id,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Division'],
    ]);

    $validator = new DepthValidator(new TeamHierarchyTraversalService());
    $validator->validate($division);

    expect($division->parent_id)->toBe($org->id);
});
