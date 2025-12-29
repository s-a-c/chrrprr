<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

final class TeamHierarchyValidator
{
    /**
     * Validate hierarchy rules for team creation/movement.
     *
     * @param  TeamType  $childType  The type of the child team
     * @param  Team|null  $parent  The parent team (null for Enterprise)
     *
     * @throws ValidationException
     */
    public static function validate(TeamType $childType, ?Team $parent): void
    {
        // 1. Enterprise Rule
        if ($childType === TeamType::ENTERPRISE) {
            if ($parent instanceof Team) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Enterprises cannot have a parent.'],
                ]);
            }

            return;
        }

        // 2. Orphan Rule
        if (! $parent instanceof Team) {
            throw ValidationException::withMessages([
                'parent_id' => ['This team type requires a parent team.'],
            ]);
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
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$childType->value} must belong to a {$validParentType->value}, but belongs to {$parent->type->value}.",
                ],
            ]);
        }

        // 4. Depth Rule
        if ($parent->getDepth() >= 10) {
            throw ValidationException::withMessages([
                'parent_id' => ['Team hierarchy depth cannot exceed 10 levels.'],
            ]);
        }
    }
}
