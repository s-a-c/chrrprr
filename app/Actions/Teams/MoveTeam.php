<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveTeam
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

            // 1. Basic Hierarchy Validation
            TeamHierarchyValidator::validate($team->type, $newParent);

            // 2. Cycle Detection (Specific to Moving)
            if ($newParent && $newParent->isDescendantOf($team)) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Cannot move a team into its own descendant.'],
                ]);
            }

            // 3. Perform Move
            $oldTenantId = $team->tenant_id;
            $team->parent_id = $newParentId;

            // Update Tenant ID immediately for this node
            if ($newParent) {
                $newTenantId = $newParent->type === TeamType::ENTERPRISE ? $newParent->id : $newParent->tenant_id;
                $team->tenant_id = $newTenantId;
            } elseif ($team->type === TeamType::ENTERPRISE) {
                // Enterprise becomes its own tenant
                $team->tenant_id = $team->id;
            }

            $team->saveQuietly(); // Avoid triggering observers

            // 4. Handle Side Effects (Recursion)
            if ($team->tenant_id !== $oldTenantId) {
                $team->updateDescendantTenants((string) $team->tenant_id);
            }
        });
    }
}
