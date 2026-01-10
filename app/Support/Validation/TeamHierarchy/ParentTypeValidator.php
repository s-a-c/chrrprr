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

        if (! $this->isValidParentHierarchy($team->type, $parent->type)) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$team->type->value} must belong to a {$this->getExpectedParentType($team->type)->value}, but belongs to {$parent->type->value}.",
                ],
            ]);
        }
    }

    private function isValidParentHierarchy(TeamType $childType, TeamType $parentType): bool
    {
        $expectedParent = $this->getExpectedParentType($childType);

        return $expectedParent === $parentType;
    }

    private function getExpectedParentType(TeamType $childType): ?TeamType
    {
        return match ($childType) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };
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
