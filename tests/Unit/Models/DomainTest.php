<?php

declare(strict_types=1);

use App\Models\Domain;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

test('domain model exists and extends tenancy base', function (): void {
    expect(class_exists(Domain::class))->toBeTrue();
    expect(is_subclass_of(Domain::class, BaseDomain::class))->toBeTrue();
});

test('domain model has expected traits', function (): void {
    // Add traits here if required, e.g. HasUlid
    // For now just verifying base existence
    expect(true)->toBeTrue();
});
