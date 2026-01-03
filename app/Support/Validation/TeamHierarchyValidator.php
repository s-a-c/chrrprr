<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Result;

final class TeamHierarchyValidator
{
    /**
     * Validate hierarchy rules for team creation/movement.
     *
     * @param  TeamType  $childType  The type of the child team
     * @param  Team|null  $parent  The parent team (null for Enterprise)
     * @return Result<bool, string>
     */
    public static function validate(TeamType $childType, ?Team $parent): Result
    {
        // 1. Enterprise Rule
        if ($childType === TeamType::ENTERPRISE) {
            if ($parent instanceof Team) {
                return Result::failure(
                    'Enterprises cannot have a parent.',
                    ['Enterprise hierarchy validation failed']
                );
            }

            return Result::success(true, ['Enterprise hierarchy validated']);
        }

        // 2. Orphan Rule
        if (! $parent instanceof Team) {
            return Result::failure(
                'This team type requires a parent team.',
                ['Orphan team validation failed']
            );
        }

        // 3. Type Compatibility Rule
        $validParentType = match ($childType) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };

        if ($validParentType && $parent->type !== $validParentType) {
            return Result::failure(
                "{$childType->value} must belong to a {$validParentType->value}, but belongs to {$parent->type->value}.",
                ['Type compatibility validation failed']
            );
        }

        // 4. Depth Rule
        if ($parent->getDepth() >= 10) {
            return Result::failure(
                'Team hierarchy depth cannot exceed 10 levels.',
                ['Depth validation failed']
            );
        }

        return Result::success(true, ['Hierarchy validation passed']);
    }
}
