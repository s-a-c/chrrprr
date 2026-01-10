<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * Validates parent-child relationships in team hierarchy.
 *
 * Rules:
 * - Hierarchical teams: parent level < child level (level-based)
 * - Cross-functional teams: no parent required (floating)
 */
final class ParentTypeValidator implements HierarchyValidatorInterface
{
    #[Override]
    public function validate(Team $team): void
    {
        // Cross-functional (floating) teams don't require hierarchical parents
        if ($team->type->isFloating()) {
            // Floating teams should NOT have a parent
            if ($team->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Cross-functional teams like '.$team->type->label().' cannot have a parent.'],
                ]);
            }

            return;
        }

        // Enterprise is the only hierarchical type that can be root
        if ($team->type === TeamType::ENTERPRISE) {
            if ($team->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Enterprise cannot have a parent.'],
                ]);
            }

            return;
        }

        // All other hierarchical types require a parent
        if ($team->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => [$team->type->label().' requires a parent team.'],
            ]);
        }

        $parent = $this->resolveParent($team);

        if (! $parent instanceof Team) {
            throw ValidationException::withMessages([
                'parent_id' => ['The specified parent team does not exist.'],
            ]);
        }

        // Parent must be hierarchical
        if ($parent->type->isFloating()) {
            throw ValidationException::withMessages([
                'parent_id' => ['Cannot use a cross-functional team as parent.'],
            ]);
        }

        // Level-based validation: child level must be > parent level
        $childLevel = $team->type->level();
        $parentLevel = $parent->type->level();

        if ($childLevel === null || $parentLevel === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Invalid parent relationship.'],
            ]);
        }

        if ($childLevel <= $parentLevel) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$team->type->label()} (level {$childLevel}) cannot have {$parent->type->label()} (level {$parentLevel}) as parent.",
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
        return Team::query()
            ->withoutGlobalScopes()
            ->where('id', $team->parent_id)
            ->whereNull('deleted_at')
            ->first();
    }
}
