<?php

declare(strict_types=1);

use App\Enums\TeamType;

test('team type enum TeamTypeTest correct values', function (): void {
    expect(TeamType::cases())->not->toBeEmpty();

    expect(TeamType::ENTERPRISE->value)->toBe('enterprise');
    expect(TeamType::ORGANISATION->value)->toBe('organisation');
    expect(TeamType::DIVISION->value)->toBe('division');
    expect(TeamType::DEPARTMENT->value)->toBe('department');
    expect(TeamType::PROJECT->value)->toBe('project');
});

test('team type enum TeamTypeTest labels', function (): void {
    expect(TeamType::ENTERPRISE->label())->toBe('Enterprise');
    expect(TeamType::ORGANISATION->label())->toBe('Organisation');
    expect(TeamType::DIVISION->label())->toBe('Division');
    expect(TeamType::DEPARTMENT->label())->toBe('Department');
    expect(TeamType::PROJECT->label())->toBe('Project');
});
