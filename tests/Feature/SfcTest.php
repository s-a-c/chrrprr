<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the sfc correctly on a manual route', function (): void {
    actingAs($user = User::factory()->create());

    get(route('test-sfc'))->assertStatus(200)->assertSee('SFC is working!');
})->skip('Legacy test with missing route');
