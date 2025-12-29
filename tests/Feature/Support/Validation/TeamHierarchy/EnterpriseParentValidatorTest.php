<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamHierarchy;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Support\Validation\TeamHierarchy\EnterpriseParentValidator;
use Illuminate\Validation\ValidationException;

it('allows enterprise without parent', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $validator = new EnterpriseParentValidator();
    $validator->validate($enterprise);

    expect($enterprise->parent_id)->toBeNull();
});

it('throws exception when enterprise has parent', function (): void {
    $parent = Enterprise::factory()->create(['name' => ['en' => 'Parent Enterprise']]);
    $enterprise = Enterprise::factory()->create([
        'name' => ['en' => 'Child Enterprise'],
        'parent_id' => $parent->id,
    ]);

    $validator = new EnterpriseParentValidator();

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Enterprises cannot have a parent team.');

    $validator->validate($enterprise);
});

it('allows non-enterprise teams with parent', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $validator = new EnterpriseParentValidator();
    $validator->validate($org);

    expect($org->parent_id)->toBe($enterprise->id);
});
