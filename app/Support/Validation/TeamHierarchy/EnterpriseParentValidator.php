<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

final class EnterpriseParentValidator implements HierarchyValidatorInterface
{
    public function validate(Team $team): void
    {
        if ($team->type === TeamType::ENTERPRISE && $team->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Enterprises cannot have a parent team.'],
            ]);
        }
    }
}
