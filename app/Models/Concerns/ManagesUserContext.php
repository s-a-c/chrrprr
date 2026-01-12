<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organisation;

/**
 * Trait for managing user context using collection pipelines.
 */
trait ManagesUserContext
{
    /**
     * Switch the user's current context to a specific organisation.
     */
    public function switchContext(Organisation $organisation): bool
    {
        $hasAccess = $this->accessibleOrganisations()
            ->where('organisation_id', $organisation->id)
            ->exists();

        if (! $hasAccess) {
            return false;
        }

        return $this->update(['current_context_id' => $organisation->id]);
    }

    /**
     * Validate the user's current context and default if necessary.
     *
     * Uses collection pipeline to find valid organisation.
     */
    public function validateContext(): void
    {
        $validOrg = $this->accessibleOrganisations()
            ->where('organisation_id', $this->current_context_id)
            ->first() ?? $this->accessibleOrganisations()
            ->orderBy('organisation_id')
            ->first();

        $this->update(['current_context_id' => $validOrg?->id]);
    }
}
