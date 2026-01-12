<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('can render the chrrps page', function (): void {
    actingAs($this->user);

    $response = get('/chrrps');

    $response->assertSuccessful();
});

it('displays the form for creating a new chrrp', function (): void {
    actingAs($this->user);

    $response = get('/chrrps');

    $response->assertSee('Chrrp', false);
    $response->assertSee('wire:submit', false);
    $response->assertSee('wire:model', false);
});

it('displays all chrrps from the computed property', function (): void {
    actingAs($this->user);

    $response = get('/chrrps');

    $response->assertSee('Jane Doe');
    $response->assertSee('Just deployed my first Laravel app! 🚀');
    $response->assertSee('5 minutes ago');

    $response->assertSee('John Smith');
    $response->assertSee('Laravel makes web development fun again!');
    $response->assertSee('1 hour ago');

    $response->assertSee('Alice Johnson');
    $response->assertSee('Working on something cool with Chrrprr...');
    $response->assertSee('3 hours ago');
});

it('requires authentication to access the chrrps page', function (): void {
    $response = get('/chrrps');

    $response->assertRedirect('/login');
});
