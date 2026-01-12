<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Validation\TeamName;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Support\Validation\TeamName\StringNameQueryBuilder;

it('applies constraints for string names across locales', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    Organisation::factory()->create([
        'name' => ['en' => 'Test Organisation'],
        'parent_id' => $enterprise->id,
    ]);

    $builder = new StringNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, 'Test Organisation', new Organisation());

    expect($query->exists())->toBeTrue();
});

it('handles empty string gracefully', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $builder = new StringNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, '', new Organisation());

    // Should not apply constraints for empty string
    expect($query->exists())->toBeFalse();
});

it('handles non-string input gracefully', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $builder = new StringNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, ['not', 'a', 'string'], new Organisation());

    // Should not apply constraints for non-string
    expect($query->exists())->toBeFalse();
});

it('checks json contains for string names', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    Organisation::factory()->create([
        'name' => ['en' => 'Test Name'],
        'parent_id' => $enterprise->id,
    ]);

    $builder = new StringNameQueryBuilder();
    $query = Organisation::query()->where('parent_id', $enterprise->id);
    $builder->applyConstraints($query, 'Test Name', new Organisation());

    expect($query->exists())->toBeTrue();
});
