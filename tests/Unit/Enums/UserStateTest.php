<?php

declare(strict_types=1);

use App\Enums\UserState;

test('user state enum UserStateTest correct values', function (): void {
    expect(UserState::cases())->not->toBeEmpty();

    expect(UserState::PENDING->value)->toBe('pending');
    expect(UserState::ACTIVE->value)->toBe('active');
    expect(UserState::INACTIVE->value)->toBe('inactive');
});

test('user state enum UserStateTest labels', function (): void {
    expect(UserState::PENDING->label())->toBe('Pending');
    expect(UserState::ACTIVE->label())->toBe('Active');
    expect(UserState::INACTIVE->label())->toBe('Inactive');
});
