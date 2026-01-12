<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        $executive = $team->executive();
        if ($executive && $executive->id === $user->id) {
            return true;
        }

        $deputies = $team->deputies();

        return $deputies->contains('id', $user->id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return false
     */
    public function forceDelete(): bool
    {
        return false;
    }
}
