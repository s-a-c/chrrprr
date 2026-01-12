<?php

declare(strict_types=1);

use App\Enums\UserStatus;

test('user status enum UserStatusTest correct values', function (): void {
    expect(UserStatus::cases())->not->toBeEmpty();

    expect(UserStatus::ONLINE->value)->toBe('online');
    expect(UserStatus::OFFLINE->value)->toBe('offline');
    expect(UserStatus::AWAY->value)->toBe('away');
    expect(UserStatus::BUSY->value)->toBe('busy');
});

test('user status enum UserStatusTest labels', function (): void {
    expect(UserStatus::ONLINE->label())->toBe('Online');
    expect(UserStatus::OFFLINE->label())->toBe('Offline');
    expect(UserStatus::AWAY->label())->toBe('Away');
    expect(UserStatus::BUSY->label())->toBe('Busy');
});
