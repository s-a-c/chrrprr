<?php

declare(strict_types=1);

use App\Enums\TeamState;

test('team state enum has correct values', function (): void {
    expect(TeamState::cases())->not->toBeEmpty();

    expect(TeamState::ACTIVE->value)->toBe('active');
    expect(TeamState::INACTIVE->value)->toBe('inactive');
    expect(TeamState::ARCHIVED->value)->toBe('archived');
});

test('team state enum has labels', function (): void {
    expect(TeamState::ACTIVE->label())->toBe('Active');
    expect(TeamState::INACTIVE->label())->toBe('Inactive');
    expect(TeamState::ARCHIVED->label())->toBe('Archived');
});
