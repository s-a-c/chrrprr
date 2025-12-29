<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TeamType;
use App\Exceptions\OptimisticLockingException;
use App\Models\Team;
use App\Support\Validation\TeamNameValidator;
use Illuminate\Support\Facades\DB;

final readonly class TeamObserver
{
    public function __construct(
        private TeamNameValidator $nameValidator,
    ) {}

    /**
     * Handle the Team "creating" event.
     */
    public function creating(Team $team): void
    {
        $team->validateHierarchy();
        $this->nameValidator->validateUnique($team);
    }

    /**
     * Handle the Team "updating" event.
     */
    public function updating(Team $team): void
    {
        $team->validateHierarchy();
        $this->nameValidator->validateUnique($team);
        $this->handleOptimisticLocking($team);
        $this->handleTenantInheritance($team);
    }

    /**
     * Handle the Team "updated" event.
     */
    public function updated(Team $team): void
    {
        if ($team->wasChanged('tenant_id')) {
            $team->updateDescendantTenants((string) $team->tenant_id);
        }
    }

    /**
     * Handle optimistic locking validation.
     */
    private function handleOptimisticLocking(Team $team): void
    {
        // Get the current lock version from the model (may be null if not loaded)
        $currentLockVersion = $team->lock_version ?? 0;

        // Get the actual lock version from the database
        $databaseLockVersion = (int) DB::table('teams')->where('id', $team->id)->value('lock_version');

        // If the model's lock_version doesn't match the database, it's stale
        throw_if($databaseLockVersion !== $currentLockVersion, OptimisticLockingException::class);

        // Increment the lock version
        $team->lock_version = $databaseLockVersion + 1;
    }

    /**
     * Handle tenant inheritance when parent changes.
     */
    private function handleTenantInheritance(Team $team): void
    {
        if (! $team->isDirty('parent_id')) {
            return;
        }

        if ($team->parent_id) {
            $parent = Team::query()->withoutGlobalScopes()->find($team->parent_id);
            if ($parent) {
                $newTenantId = $parent->type === TeamType::ENTERPRISE ? $parent->id : $parent->tenant_id;
                $team->tenant_id = $newTenantId;
            }

            return;
        }

        if ($team->type === TeamType::ENTERPRISE) {
            // If parent_id becomes null, it must be an Enterprise (checked by validateHierarchy)
            // and it becomes its own tenant.
            $team->tenant_id = $team->id;
        }
    }
}
