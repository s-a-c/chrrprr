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

        if ($this->traversalService->getDepth($parent) >= 10) {
            throw ValidationException::withMessages([
                'parent_id' => ['Team hierarchy depth cannot exceed 10 levels.'],
            ]);
        }
    }
}
