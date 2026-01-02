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
        $parent = $this->resolveParent($team);

        if (! $parent instanceof Team) {
            return;
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
        if ($team->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['This team type requires a parent team.'],
            ]);
        }

        return $team->parent ?? $team
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('id', $team->parent_id)
            ->whereNull('deleted_at')
            ->first();
    }
}
