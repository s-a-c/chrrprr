<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TeamType;
use App\Exceptions\OptimisticLockingException;
use App\Models\Team;
use App\Support\Validation\TeamNameValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class TeamObserver
{
    /**
     * Handle the Team "creating" event.
     */
    public function creating(Team $team): void
    {
        // Calculate tenant_id if not already set
        if ($team->tenant_id === null) {
            $this->updateTenantId($team);
        }

        // Validate unique name among siblings
        $this->validateUniqueName($team);
    }

    /**
     * Handle the Team "updating" event.
     */
    public function updating(Team $team): void
    {
        // Enforce immutability of certain fields if needed
        // For example, tenant_id usually shouldn't change after creation
        if ($team->isDirty('tenant_id') && $team->getOriginal('tenant_id') !== null) {
            /** @var mixed $originalTenantId */
            $originalTenantId = $team->getOriginal('tenant_id');
            $team->tenant_id = $originalTenantId;
        }

        // Validate unique name if name is being changed
        if ($team->isDirty('name')) {
            $this->validateUniqueName($team);
        }

        // Update tenant_id if parent_id is being changed
        if ($team->isDirty('parent_id')) {
            $this->updateTenantId($team);
        }
    }

    /**
     * Handle the Team "saving" event.
     * Increment lock_version on save.
     */
    public function saving(Team $team): void
    {
        // Only valid for existing models (updates)
        if ($team->exists && ! $team->isDirty('lock_version')) {
            $currentDbVersion = (int) DB::table('teams')->where('id', $team->id)->value('lock_version');
            $originalVersion = (int) ($team->getOriginal('lock_version') ?? 0);
            throw_if($currentDbVersion !== $originalVersion, OptimisticLockingException::class);
        }

        // Only increment if the model is dirty (has changes) and we're not already setting lock_version
        if ($team->isDirty() && ! $team->isDirty('lock_version')) {
            /** @var int $currentVersion */
            $currentVersion = (int) ($team->getOriginal('lock_version') ?? $team->lock_version ?? 0);
            $team->lock_version = $currentVersion + 1;
        }
    }

    /**
     * Validate that the team name is unique among siblings.
     */
    private function validateUniqueName(Team $team): void
    {
        $validator = resolve(TeamNameValidator::class);
        $result = $validator->validateUnique($team);

        if ($result->isFailure) {
            throw ValidationException::withMessages([
                'name' => [$result->error],
            ]);
        }
    }

    /**
     * Update tenant_id when parent_id changes.
     */
    private function updateTenantId(Team $team): void
    {
        $newParentId = $team->parent_id;

        if ($newParentId === null) {
            // If parent is removed and team is Enterprise, it becomes its own tenant
            if ($team->type === TeamType::ENTERPRISE) {
                $team->tenant_id = $team->id;
            }

            return;
        }

        // Load the new parent to determine tenant
        $newParent = Team::query()
            ->withoutGlobalScopes()
            ->where('id', $newParentId)
            ->whereNull('deleted_at')
            ->first();

        if ($newParent) {
            $team->tenant_id = $newParent->type === TeamType::ENTERPRISE
                ? $newParent->id
                : $newParent->tenant_id;
        }
    }
}
