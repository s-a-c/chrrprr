<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MoveTeam
{
    /**
     * Move a team to a new parent with cycle detection and tenant propagation.
     */
    public function handle(Team $team, ?int $newParentId): void
    {
        if ($team->parent_id === $newParentId) {
            return;
        }

        DB::transaction(function () use ($team, $newParentId): void {
            $newParent = $newParentId ? Team::query()->withoutGlobalScopes()->find($newParentId) : null;

            TeamHierarchyValidator::validate($team->type, $newParent);
            $this->validateNoCycle($team, $newParent);

            $oldTenantId = (string) $team->tenant_id;
            $team->parent_id = $newParentId;
            $team->tenant_id = $this->calculateNewTenantId($team, $newParent);
            $team->saveQuietly(); // Avoid triggering observers

            $this->propagateTenantChange($team, $oldTenantId);
        });
    }

    /**
     * Validate that moving would not create a cycle.
     */
    private function validateNoCycle(Team $team, ?Team $newParent): void
    {
        if ($newParent && $newParent->isDescendantOf($team)) {
            throw ValidationException::withMessages([
                'parent_id' => ['Cannot move a team into its own descendant.'],
            ]);
        }
    }

    /**
     * Calculate the new tenant ID based on the new parent.
     */
    private function calculateNewTenantId(Team $team, ?Team $newParent): string
    {
        if ($newParent instanceof Team) {
            return $newParent->type === TeamType::ENTERPRISE ? (string) $newParent->id : (string) $newParent->tenant_id;
        }

        if ($team->type === TeamType::ENTERPRISE) {
            // Enterprise becomes its own tenant
            return (string) $team->id;
        }

        return (string) $team->tenant_id;
    }

    /**
     * Propagate tenant changes to descendants if tenant changed.
     */
    private function propagateTenantChange(Team $team, string $oldTenantId): void
    {
        if ($team->tenant_id !== $oldTenantId) {
            $team->updateDescendantTenants((string) $team->tenant_id);
        }
    }
}
