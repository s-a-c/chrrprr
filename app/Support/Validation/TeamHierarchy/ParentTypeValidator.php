<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;
use Override;

final class ParentTypeValidator implements HierarchyValidatorInterface
{
    #[Override]
    public function validate(Team $team): void
    {
        // Teams that require a parent
        $requiresParent = match ($team->type) {
            TeamType::ORGANISATION, TeamType::DIVISION, TeamType::DEPARTMENT, TeamType::PROJECT => true,
            default => false,
        };

        if ($requiresParent && $team->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['This team type requires a parent team.'],
            ]);
        }

        if ($team->parent_id === null) {
            return; // No parent to validate
        }

        $parent = $this->resolveParent($team);

        if (! $parent instanceof Team) {
            throw ValidationException::withMessages([
                'parent_id' => ['The specified parent team does not exist.'],
            ]);
        }

        $validParentType = match ($team->type) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };

        if ($validParentType && $parent->type !== $validParentType) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$team->type->value} must belong to a {$validParentType->value}, but belongs to {$parent->type->value}.",
                ],
            ]);
        }
    }

    private function resolveParent(Team $team): ?Team
    {
        // If parent relationship is already loaded, use it
        if ($team->relationLoaded('parent')) {
            return $team->parent;
        }

        // Otherwise, query for the parent
        // Use Team::query() instead of $team->newQuery() to ensure we get the correct model
        return Team::query()
            ->withoutGlobalScopes()
            ->where('id', $team->parent_id)
            ->whereNull('deleted_at')
            ->first();
    }
}
