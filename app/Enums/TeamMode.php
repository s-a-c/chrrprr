<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the organizational mode of a Team.
 *
 * - HIERARCHICAL: Line management structure (parent-child tree)
 * - CROSS_FUNCTIONAL: Floating membership-based (no parent required)
 */
enum TeamMode: string
{
    case HIERARCHICAL = 'hierarchical';
    case CROSS_FUNCTIONAL = 'cross_functional';

    public function label(): string
    {
        return match ($this) {
            self::HIERARCHICAL => 'Hierarchical',
            self::CROSS_FUNCTIONAL => 'Cross-Functional',
        };
    }

    public function isFloating(): bool
    {
        return $this === self::CROSS_FUNCTIONAL;
    }
}
