<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Models\Team;
use App\Services\TeamHierarchyTraversalService;
use Illuminate\Validation\ValidationException;
use Override;

final readonly class CycleValidator implements HierarchyValidatorInterface
{
    public function __construct(
        private TeamHierarchyTraversalService $traversalService,
    ) {}

    #[Override]
    public function validate(Team $team): void
    {
        if (! $team->id) {
            return;
        }

        if ((int) $team->parent_id === (int) $team->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be its own parent.'],
            ]);
        }

        // Get the new parent (use the parent_id that's being set, not the relationship)
        $newParentId = $team->parent_id;
        if ($newParentId === null) {
            return; // No parent, no cycle possible
        }

        // Load the new parent to check if it's a descendant
        $newParent = $team->newQuery()
            ->withoutGlobalScopes()
            ->where('id', $newParentId)
            ->whereNull('deleted_at')
            ->first();

        if ($newParent && $this->traversalService->isDescendantOf($newParent, $team)) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be moved into its own descendant (would create a cycle).'],
            ]);
        }
    }
}
