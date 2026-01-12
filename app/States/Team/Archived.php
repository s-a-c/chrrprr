<?php

declare(strict_types=1);

namespace App\States\Team;

final class Archived extends TeamState
{
    /**
     * Get the human-readable label for the state.
     */
    public function label(): string
    {
        return 'Archived';
    }
}
