<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateTeam
{
    /**
     * Create a new team with proper validation and transaction control.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Team
    {
        /** @var Team */
        return DB::transaction(static function () use ($data): Team {
            /** @var TeamType|string|int|null $typeValue */
            $typeValue = $data['type'] ?? null;
            if (! ($typeValue instanceof TeamType)) {
                throw_if(! is_string($typeValue) && ! is_int($typeValue), InvalidArgumentException::class, 'Type must be a TeamType instance, string, or int');
                /** @var string|int $typeValue */
                $type = TeamType::from($typeValue);
            } else {
                $type = $typeValue;
            }

            $parentId = $data['parent_id'] ?? null;

            // Resolve Parent
            /** @var Team|null $parent */
            $parent = $parentId ? Team::query()->withoutGlobalScopes()->find($parentId) : null;

            // Run Validation
            TeamHierarchyValidator::validate($type, $parent);

            // Determine Tenant (Enterprise logic)
            $tenantId = null;
            if ($parent instanceof Team) {
                $tenantId = $parent->type === TeamType::ENTERPRISE ? $parent->id : $parent->tenant_id;
            }

            if (! $parent && $type === TeamType::ENTERPRISE) {
                $tenantId = null; // Will be set to self ID after creation
            }

            // Create team (observer will validate hierarchy and unique name)
            $team = Team::query()->create([
                ...$data,
                'tenant_id' => $tenantId,
            ]);

            // Fix Enterprise Self-Referential Tenant ID
            if ($type === TeamType::ENTERPRISE) {
                $team->updateQuietly(['tenant_id' => $team->id]);
            }

            return $team;
        });
    }
}
