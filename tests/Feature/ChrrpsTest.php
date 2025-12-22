<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('can render the chrrps page', function () {
    actingAs($user = User::factory()->create());

    $response = get('/chrrps');

    $response->assertStatus(200);
    $response->assertSee('Just deployed my first Laravel app!');
    $response->assertSee($user->name);
});
