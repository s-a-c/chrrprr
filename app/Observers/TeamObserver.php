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
     * Validate unique name before creation.
     */
    public function creating(Team $team): void
    {
        $this->validateUniqueName($team);
    }

    /**
     * Handle the Team "updating" event.
     * Check optimistic locking, validate unique name, and update tenant_id if parent_id changes.
     */
    public function updating(Team $team): void
    {
        // Only check if lock_version is set and the model is dirty
        if ($team->isDirty() && $team->lock_version !== null) {
            $currentVersion = (int) DB::table('teams')
                ->where('id', $team->id)
                ->value('lock_version');
            throw_if($currentVersion !== $team->getOriginal('lock_version'), OptimisticLockingException::class, 'The team has been modified by another process.');
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
        // Only increment if the model is dirty (has changes) and we're not already setting lock_version
        if ($team->isDirty() && ! $team->isDirty('lock_version')) {
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
