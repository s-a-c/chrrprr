<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Models\Team;
use Illuminate\Validation\ValidationException;

interface HierarchyValidatorInterface
{
    /**
     * Validate the team hierarchy rule.
     *
     * @throws ValidationException
     */
    public function validate(Team $team): void;
}
