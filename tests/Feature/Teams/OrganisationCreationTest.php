<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;

test('can create organisation under enterprise', function (): void {
    $enterprise = Enterprise::factory()->create();

    $organisation = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'tenant_id' => $enterprise->id,
    ]);

    expect($organisation)
        ->toBeInstanceOf(Organisation::class)
        ->parent_id->toBe($enterprise->id)
        ->tenant_id->toBe($enterprise->id);
});
