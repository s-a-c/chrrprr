<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;

test('user status can be set to online', function (): void {
    $user = User::factory()->create(['status' => UserStatus::OFFLINE]);

    $user->status = UserStatus::ONLINE;
    $user->save();

    expect($user->fresh()->status)->toBe(UserStatus::ONLINE);
});

test('user status can be nullable', function (): void {
    $user = User::factory()->create(['status' => null]);

    expect($user->fresh()->status)->toBeNull();
});

test('user status is cast to enum', function (): void {
    $user = User::factory()->create(['status' => UserStatus::AWAY]);

    expect($user->status)->toBeInstanceOf(UserStatus::class)->and($user->status)->toBe(UserStatus::AWAY);
});

test('user status has label method', function (): void {
    expect(UserStatus::ONLINE->label())
        ->toBe('Online')
        ->and(UserStatus::OFFLINE->label())
        ->toBe('Offline')
        ->and(UserStatus::AWAY->label())
        ->toBe('Away')
        ->and(UserStatus::BUSY->label())
        ->toBe('Busy');
});

test('user can transition between all statuses', function (): void {
    $user = User::factory()->create(['status' => UserStatus::OFFLINE]);

    $user->status = UserStatus::ONLINE;
    $user->save();
    expect($user->fresh()->status)->toBe(UserStatus::ONLINE);

    $user->status = UserStatus::AWAY;
    $user->save();
    expect($user->fresh()->status)->toBe(UserStatus::AWAY);

    $user->status = UserStatus::BUSY;
    $user->save();
    expect($user->fresh()->status)->toBe(UserStatus::BUSY);

    $user->status = UserStatus::OFFLINE;
    $user->save();
    expect($user->fresh()->status)->toBe(UserStatus::OFFLINE);
});
