<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Models\Team;
use App\Services\TeamHierarchyTraversalService;
use Illuminate\Validation\ValidationException;
use Override;

final readonly class DepthValidator implements HierarchyValidatorInterface
{
    public function __construct(
        private TeamHierarchyTraversalService $traversalService,
    ) {}

    #[Override]
    public function validate(Team $team): void
    {
        $parent = $team->parent;

        if (! $parent) {
            return;
        }

        $depth = $this->traversalService->getDepth($parent);
        $hardLimit = config('teams.hierarchy.hard_depth_limit', 10);
        $softLimit = $team->tenant->depth_limit ?? config('teams.hierarchy.default_soft_depth_limit', 5);

        if ($depth >= $hardLimit) {
            throw ValidationException::withMessages([
                'parent_id' => ["Team hierarchy depth cannot exceed {$hardLimit} levels."],
            ]);
        }

        if ($depth >= $softLimit) {
            throw ValidationException::withMessages([
                'parent_id' => ["Team hierarchy depth exceeds tenant limit of {$softLimit} levels."],
            ]);
        }
    }
}
