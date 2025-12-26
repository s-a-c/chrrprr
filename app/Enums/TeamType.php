<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamType: string
{
    case ENTERPRISE = 'enterprise';
    case ORGANISATION = 'organisation';
    case DIVISION = 'division';
    case DEPARTMENT = 'department';
    case PROJECT = 'project';

    public function label(): string
    {
        return match ($this) {
            self::ENTERPRISE => 'Enterprise',
            self::ORGANISATION => 'Organisation',
            self::DIVISION => 'Division',
            self::DEPARTMENT => 'Department',
            self::PROJECT => 'Project',
        };
    }
}
