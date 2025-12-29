<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait HasTeamHierarchy
{
    /**
     * Update tenant IDs for all descendants recursively.
     */
    public function updateDescendantTenants(string $newTenantId): void
    {
        foreach ($this->children()->withoutGlobalScopes()->get() as $child) {
            $child->tenant_id = $newTenantId;
            $child->save(); // Triggers updated recursively
        }
    }

    /**
     * Validate team hierarchy rules.
     */
    public function validateHierarchy(): void
    {
        $type = $this->normalizeType();

        if (! $type) {
            return;
        }

        $this->validateEnterpriseParent($type);

        if ($type === TeamType::ENTERPRISE) {
            return;
        }

        $parent = $this->resolveParent();

        if (! $parent) {
            return;
        }

        $this->validateParentType($type, $parent);
        $this->validateNoCycles($parent);
        $this->validateDepth($parent);
    }

    /**
     * Check if this team is a descendant of the given team.
     */
    public function isDescendantOf(Team $team): bool
    {
        $currentParentId = $this->parent_id;

        while ($currentParentId) {
            if ((int) $currentParentId === (int) $team->id) {
                return true;
            }

            $currentParentId = DB::table('teams')->where('id', $currentParentId)->value('parent_id');
        }

        return false;
    }

    /**
     * Get the depth of this team in the hierarchy.
     *
     * @psalm-return int<1, max>
     */
    public function getDepth(): int
    {
        $depth = 1;
        $currentParentId = $this->parent_id;

        while ($currentParentId) {
            $depth++;
            $currentParentId = DB::table('teams')->where('id', $currentParentId)->value('parent_id');
        }

        return $depth;
    }

    /**
     * Normalize type to enum (handles both string and enum HasTeamHierarchy).
     */
    private function normalizeType(): ?TeamType
    {
        $typeValue = $this->getAttribute('type');

        if ($typeValue instanceof TeamType) {
            return $typeValue;
        }

        if (is_string($typeValue)) {
            return TeamType::from($typeValue);
        }

        return null;
    }

    /**
     * Validate that Enterprise doesn't have a parent.
     */
    private function validateEnterpriseParent(TeamType $type): void
    {
        if ($type === TeamType::ENTERPRISE && $this->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Enterprises cannot have a parent team.'],
            ]);
        }
    }

    /**
     * Resolve the parent team, loading if necessary.
     */
    private function resolveParent(): ?Team
    {
        if ($this->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['This team type requires a parent team.'],
            ]);
        }

        $parent = $this->parent;

        if (! $parent) {
            return $this
                ->newQuery()
                ->withoutGlobalScopes()
                ->where('id', $this->parent_id)
                ->whereNull('deleted_at')
                ->first();
        }

        return $parent;
    }

    /**
     * Validate that parent type matches expected type for this team type.
     */
    private function validateParentType(TeamType $type, Team $parent): void
    {
        $validParentType = match ($type) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };

        if ($validParentType && $parent->type !== $validParentType) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$type->value} must belong to a {$validParentType->value}, but belongs to {$parent->type->value}.",
                ],
            ]);
        }
    }

    /**
     * Validate that moving wouldn't create a cycle.
     */
    private function validateNoCycles(Team $parent): void
    {
        if (! $this->id) {
            return;
        }

        if ((int) $this->parent_id === (int) $this->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be its own parent.'],
            ]);
        }

        if ($parent->isDescendantOf($this)) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be moved into its own descendant (would create a cycle).'],
            ]);
        }
    }

    /**
     * Validate that depth doesn't exceed maximum.
     */
    private function validateDepth(Team $parent): void
    {
        if ($parent->getDepth() >= 10) {
            throw ValidationException::withMessages([
                'parent_id' => ['Team hierarchy depth cannot exceed 10 levels.'],
            ]);
        }
    }
}
