<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\TeamType;
use App\Models\Team;
use App\Services\TeamHierarchyTraversalService;
use App\Support\Validation\TeamHierarchy\CycleValidator;
use App\Support\Validation\TeamHierarchy\DepthValidator;
use App\Support\Validation\TeamHierarchy\EnterpriseParentValidator;
use App\Support\Validation\TeamHierarchy\ParentTypeValidator;
use Illuminate\Support\Collection;

trait HasTeamHierarchy
{
    /**
     * Boot the trait and register model events.
     */
    public static function bootHasTeamHierarchy(): void
    {
        static::creating(static function (Team $team): void {
            $team->validateHierarchy();
        });

        static::updating(static function (Team $team): void {
            // Only validate if parent_id or type is being changed
            if ($team->isDirty(['parent_id', 'type'])) {
                $team->validateHierarchy();
            }
        });
    }

    /**
     * Update tenant IDs for all descendants recursively using collections.
     */
    public function updateDescendantTenants(string $newTenantId): void
    {
        $this->children()
            ->withoutGlobalScopes()
            ->get()
            ->each(static function (Team $child) use ($newTenantId): void {
                $child->tenant_id = $newTenantId;
                $child->save(); // Triggers updated recursively
            });
    }

    /**
     * Validate team hierarchy rules using collection-based validator orchestration.
     *
     * Replaces multiple if/else branches with collection pipeline.
     */
    public function validateHierarchy(): void
    {
        $type = $this->normalizeType();

        // Skip validation if type cannot be determined (e.g., during factory creation before type is set)
        if (! $type) {
            return;
        }

        // Ensure type is set as enum on the model for validators
        if ($this->getAttribute('type') !== $type) {
            $this->setAttribute('type', $type);
        }

        // Use collection pipeline with Higher Order Messaging
        $this->getValidators()
            ->each->validate($this); // Higher Order Messaging eliminates loop complexity
    }

    /**
     * Check if this team is a descendant of the given team.
     *
     * Delegates to traversal service for collection-based implementation.
     */
    public function isDescendantOf(Team $team): bool
    {
        return resolve(TeamHierarchyTraversalService::class)
            ->isDescendantOf($this, $team);
    }

    /**
     * Get the depth of this team in the hierarchy.
     *
     * Delegates to traversal service for collection-based implementation.
     *
     * @psalm-return int<1, max>
     */
    public function getDepth(): int
    {
        return resolve(TeamHierarchyTraversalService::class)
            ->getDepth($this);
    }

    /**
     * Get validators as a collection, conditionally adding validators based on team type.
     *
     * Uses collection 'when' method to conditionally build validator list.
     */
    private function getValidators(): Collection
    {
        return collect([new EnterpriseParentValidator()])
            ->when(
                $this->type !== TeamType::ENTERPRISE,
                static fn (Collection $validators) => $validators->concat([
                    new ParentTypeValidator(),
                    new CycleValidator(
                        resolve(TeamHierarchyTraversalService::class)
                    ),
                    new DepthValidator(
                        resolve(TeamHierarchyTraversalService::class)
                    ),
                ])
            );
    }

    /**
     * Normalize type to enum (handles both string and enum).
     * Falls back to resolving type from class mapping if attribute is missing.
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

        // Fallback: Try to resolve type from class mapping if attribute is missing
        if (property_exists($this, 'childTypes') && is_array($this->childTypes)) {
            // Find key (type) where value (class) matches current class
            $class = $this::class;
            $type = array_search($class, $this->childTypes, true);

            if ($type && is_string($type)) {
                return TeamType::tryFrom($type);
            }
        }

        return null;
    }
}
