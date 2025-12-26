<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return false
     */
    public function forceDelete(): bool
    {
        return false;
    }

    /**
     * Determine whether the user can edit the team bio.
     *
     * Team Executive, Enterprise/Organisation Admins can edit bio.
     */
    public function editBio(User $user, Team $team): bool
    {
        // Enterprise/Organisation Admins can edit any team bio within their scope
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Team Executive can edit their team's bio
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($team->id);

        try {
            return $user->hasRole('executive');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
