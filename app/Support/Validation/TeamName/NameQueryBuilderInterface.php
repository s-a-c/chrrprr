<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

interface NameQueryBuilderInterface
{
    /**
     * Apply name constraints to the query.
     */
    public function applyConstraints(Builder $query, mixed $name, Team $team): void;
}
