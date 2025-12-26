<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    /**
     * Create a new team with proper validation and transaction control.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Team
    {
        return DB::transaction(function () use ($data): Team {
            $type = $data['type'] instanceof TeamType ? $data['type'] : TeamType::from($data['type']);
            $parentId = $data['parent_id'] ?? null;

            // Resolve Parent
            $parent = $parentId ? Team::query()->withoutGlobalScopes()->find($parentId) : null;

            // Run Validation
            TeamHierarchyValidator::validate($type, $parent);

            // Determine Tenant (Enterprise logic)
            $tenantId = null;
            if ($parent) {
                $tenantId = $parent->type === TeamType::ENTERPRISE ? $parent->id : $parent->tenant_id;
            } elseif ($type === TeamType::ENTERPRISE) {
                $tenantId = null; // Will be set to self ID after creation
            }

            // Create
            $team = Team::query()->create([
                ...$data,
                'tenant_id' => $tenantId,
            ]);

            // Fix Enterprise Self-Referential Tenant ID
            if ($type === TeamType::ENTERPRISE) {
                $team->updateQuietly(['tenant_id' => $team->id]);
            }

            // Validate unique name (still required)
            $team->validateUniqueName();

            return $team;
        });
    }
}
