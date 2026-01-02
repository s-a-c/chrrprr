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

        $parent = $team->parent;
        if ($parent && $this->traversalService->isDescendantOf($parent, $team)) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be moved into its own descendant (would create a cycle).'],
            ]);
        }
    }
}
