<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Contracts\CommandHandler;
use App\Handlers\BaseHandler;
use App\Models\Team;
use App\Models\User;
use App\Support\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * Assign Executive Command Handler.
 *
 * Handles executive assignment using the monadic CQRS pattern.
 * Migrated from App\Actions\Teams\AssignExecutive.
 */
final class AssignExecutiveHandler extends BaseHandler implements CommandHandler
{
    /**
     * Handle the AssignExecutive command.
     *
     * @param  AssignExecutiveCommand  $command  The command to handle
     * @return Result<Team>
     */
    #[Override]
    public function handle(object $command): Result
    {
        if (! $command instanceof AssignExecutiveCommand) {
            return Result::failure('Invalid command type', ['Expected AssignExecutiveCommand']);
        }

        /** @return Result<Team> */
        return Result::try(
            fn (): Team => DB::transaction(function () use ($command): Team {
                $this->assignExecutive($command);

                return $command->team;
            }),
            ['Starting executive assignment']
        );
    }

    /**
     * Assign the executive to the team.
     *
     * Note: ValidationException will be caught by Result::try() and converted to Result failures.
     *
     * @throws ValidationException
     */
    private function assignExecutive(AssignExecutiveCommand $command): void
    {
        // Validate constraints using team's method
        $command->team->validateExecutiveDeputyConstraints($command->executive, 'executive');

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($command->team->id);

        try {
            // Check if team already has an executive
            /** @var User|null $existing */
            $existing = User::query()
                ->role('executive')
                ->where('id', '!=', $command->executive->id)
                ->first();

            if ($existing instanceof User) {
                throw ValidationException::withMessages([
                    'executive' => ['This team already has an executive assigned.'],
                ]);
            }

            // Check if user is already executive
            if ($command->executive->hasRole('executive')) {
                return;
            }

            $command->executive->assignRole('executive');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
