<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamState: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ARCHIVED => 'Archived',
        };
    }
}
