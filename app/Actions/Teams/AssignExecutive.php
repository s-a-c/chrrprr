<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class AssignExecutive
{
    /**
     * Assign an executive to a team.
     */
    public function handle(Team $team, User $user): void
    {
        // Validate constraints using team's method
        $team->validateExecutiveDeputyConstraints($user, 'executive');

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($team->id);

        try {
            // Check if team already has an executive
            /** @var User|null $existing */
            $existing = User::query()
                ->role('executive')
                ->where('id', '!=', $user->id)
                ->first();

            if ($existing instanceof User) {
                throw ValidationException::withMessages([
                    'executive' => ['This team already has an executive assigned.'],
                ]);
            }

            // Check if user is already executive
            if ($user->hasRole('executive')) {
                return;
            }

            $user->assignRole('executive');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
