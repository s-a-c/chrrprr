<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamName;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Support\Validation\TeamName\ArrayNameQueryBuilder;

it('applies constraints for array-based names', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    Organisation::factory()->create([
        'name' => ['en' => 'Existing Org', 'es' => 'Org Existente'],
        'parent_id' => $enterprise->id,
    ]);

    $builder = new ArrayNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, ['en' => 'Existing Org'], new Organisation());

    expect($query->exists())->toBeTrue();
});

it('filters out null and empty values', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    Organisation::factory()->create([
        'name' => ['en' => 'Valid Name'],
        'parent_id' => $enterprise->id,
    ]);

    $builder = new ArrayNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, ['en' => null, 'es' => '', 'fr' => 'Valid Name'], new Organisation());

    // Should not match because 'Valid Name' is in 'fr' locale, not 'en'
    expect($query->exists())->toBeFalse();
});

it('handles non-array input gracefully', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $builder = new ArrayNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, 'not an array', new Organisation());

    // Should not apply any constraints, so query should not match
    expect($query->exists())->toBeFalse();
});
