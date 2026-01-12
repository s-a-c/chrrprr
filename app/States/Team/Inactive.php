<?php

declare(strict_types=1);

namespace App\States\Team;

final class Inactive extends TeamState
{
    /**
     * Get the human-readable label for the state.
     */
    public function label(): string
    {
        return 'Inactive';
    }
}
