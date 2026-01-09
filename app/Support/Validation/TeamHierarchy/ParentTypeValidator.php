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
        // Only Enterprise can exist without a parent
        $requiresParent = $team->type !== TeamType::ENTERPRISE;

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

        // Parent must be higher in the hierarchy than the child
        // Hierarchy order (highest to lowest): Enterprise > Organisation > Division > Department > Project
        $hierarchyRanks = [
            TeamType::ENTERPRISE->value => 1,
            TeamType::ORGANISATION->value => 2,
            TeamType::DIVISION->value => 3,
            TeamType::DEPARTMENT->value => 4,
            TeamType::PROJECT->value => 5,
        ];

        $parentRank = $hierarchyRanks[$parent->type->value] ?? 999;
        $childRank = $hierarchyRanks[$team->type->value] ?? 0;

        if ($parentRank >= $childRank) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$team->type->value} must belong to a higher-level team type, but {$parent->type->value} is not higher than {$team->type->value}.",
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
