<?php

declare(strict_types=1);

use App\Enums\TeamStatus;

test('team status enum has correct values', function (): void {
    expect(TeamStatus::cases())->not->toBeEmpty();

    expect(TeamStatus::ONLINE->value)->toBe('online');
    expect(TeamStatus::OFFLINE->value)->toBe('offline');
    expect(TeamStatus::AWAY->value)->toBe('away');
    expect(TeamStatus::BUSY->value)->toBe('busy');
});

test('team status enum has labels', function (): void {
    expect(TeamStatus::ONLINE->label())->toBe('Online');
    expect(TeamStatus::OFFLINE->label())->toBe('Offline');
    expect(TeamStatus::AWAY->label())->toBe('Away');
    expect(TeamStatus::BUSY->label())->toBe('Busy');
});
