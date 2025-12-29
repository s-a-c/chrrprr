<?php

declare(strict_types=1);

use App\Enums\UserState;
use App\Models\User;

test('user can transition from pending to active state', function (): void {
    $user = User::factory()->create(['state' => UserState::PENDING]);

    $user->state = UserState::ACTIVE;
    $user->save();

    expect($user->fresh()->state)->toBe(UserState::ACTIVE);
});

test('user can transition from active to inactive state', function (): void {
    $user = User::factory()->create(['state' => UserState::ACTIVE]);

    $user->state = UserState::INACTIVE;
    $user->save();

    expect($user->fresh()->state)->toBe(UserState::INACTIVE);
});

test('user state is cast to enum', function (): void {
    $user = User::factory()->create(['state' => UserState::ACTIVE]);

    expect($user->state)->toBeInstanceOf(UserState::class)->and($user->state)->toBe(UserState::ACTIVE);
});

test('user state has label method', function (): void {
    expect(UserState::PENDING->label())
        ->toBe('Pending')
        ->and(UserState::ACTIVE->label())
        ->toBe('Active')
        ->and(UserState::INACTIVE->label())
        ->toBe('Inactive');
});
