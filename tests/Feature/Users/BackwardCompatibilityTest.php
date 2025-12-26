<?php

declare(strict_types=1);

use App\Models\User;

test('user can still be found by integer id', function (): void {
    $user = User::factory()->create();

    $found = User::find($user->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($user->id)
        ->and($found->ulid)->toBe($user->ulid);
});

test('user has both id and ulid', function (): void {
    $user = User::factory()->create();

    expect($user->id)->toBeInt()
        ->and($user->ulid)->toBeString()
        ->and(mb_strlen($user->ulid))->toBe(26);
});

test('user primary key is still integer id', function (): void {
    $user = new User();

    expect($user->getKeyName())->toBe('id')
        ->and($user->getRouteKeyName())->toBe('ulid'); // Route uses ULID
});

test('user can be queried by id for foreign key relationships', function (): void {
    $user = User::factory()->create();

    // Foreign keys should still use integer id
    $found = User::where('id', $user->id)->first();

    expect($found)->not->toBeNull()
        ->and($found->ulid)->toBe($user->ulid);
});

test('user ulid is unique', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->ulid)->not->toBe($user2->ulid);
});

test('user route model binding supports backward compatibility with integer id', function (): void {
    $user = User::factory()->create();

    // Route should work with ULID
    Route::middleware('web')->group(function (): void {
        Route::get('/test-users/{user}', function (User $user): string {
            return (string) $user->id;
        })->name('test.users.show');
    });

    $response = $this->get("/test-users/{$user->ulid}");
    $response->assertSuccessful();
    expect($response->content())->toBe((string) $user->id);

    // Route should also work with integer ID for backward compatibility
    $response = $this->get("/test-users/{$user->id}");
    $response->assertSuccessful();
    expect($response->content())->toBe((string) $user->id);
});
