<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    // Create a test route that uses route model binding
    Route::middleware('web')->group(function (): void {
        Route::get('/test-users/{user}', fn (User $user): string => (string) $user->id)->name('test.users.show');
    });
});

test('user can be resolved by ulid in route model binding', function (): void {
    $user = User::factory()->create();

    $response = $this->get("/test-users/{$user->ulid}");

    $response->assertSuccessful();
    $response->assertSee($user->id);
});

test('user route model binding fails with invalid ulid', function (): void {
    $response = $this->get('/test-users/invalid-ulid-12345');

    $response->assertNotFound();
});

test('user route model binding fails with non-existent ulid', function (): void {
    $nonExistentUlid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    $response = $this->get("/test-users/{$nonExistentUlid}");

    $response->assertNotFound();
});

test('user route uses ulid as route key name', function (): void {
    $user = User::factory()->create();

    expect($user->getRouteKeyName())->toBe('ulid');
});

test('user can be found by ulid scope', function (): void {
    $user = User::factory()->create();

    $found = User::query()->byUlid($user->ulid)->first();

    expect($found)->not->toBeNull()->and($found->id)->toBe($user->id);
});
