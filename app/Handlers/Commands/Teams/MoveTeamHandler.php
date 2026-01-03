<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Contracts\CommandHandler;
use App\Enums\TeamType;
use App\Handlers\BaseHandler;
use App\Models\Team;
use App\Support\Result;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;
use Override;
use RuntimeException;

/**
 * Move Team Command Handler.
 *
 * Handles team moves using the monadic CQRS pattern.
 * Migrated from App\Actions\Teams\MoveTeam.
 */
final class MoveTeamHandler extends BaseHandler implements CommandHandler
{
    /**
     * Handle the MoveTeam command.
     *
     * @param  MoveTeamCommand  $command  The command to handle
     */
    #[Override]
    public function handle(object $command): Result
    {
        if (! $command instanceof MoveTeamCommand) {
            return Result::failure('Invalid command type', ['Expected MoveTeamCommand']);
        }

        // Early return if no move needed
        if ($command->team->parent_id === $command->newParentId) {
            return Result::success($command->team, ['No move needed - parent unchanged']);
        }

        $newParent = $command->newParentId !== null
            ? Team::query()->withoutGlobalScopes()->find($command->newParentId)
            : null;

        // Validate hierarchy rules using flatMap
        return TeamHierarchyValidator::validate($command->team->type, $newParent)
            ->flatMap(
                // Validate no cycle would be created using guard()

                fn (): Result => $this->guard(
                    ! ($newParent instanceof Team && $newParent->isDescendantOf($command->team)),
                    'Cannot move a team into its own descendant.'
                )->flatMap(
                    // Execute move in transaction

                    fn (): Result => Result::try(
                        fn (): Team => DB::transaction(fn (): Team => $this->moveTeam($command, $newParent)),
                        ['Executing team move']
                    )->flatMap(
                        // Ensure team was found after move

                        fn (mixed $team): Result => $this->ensureFound($team instanceof Team ? $team : throw new RuntimeException('Invalid team type'), 'Team not found after move'))));
    }

    /**
     * Move the team to a new parent with tenant propagation.
     */
    private function moveTeam(MoveTeamCommand $command, ?Team $newParent): Team
    {
        $oldTenantId = (string) $command->team->tenant_id;
        $command->team->parent_id = $command->newParentId !== null ? (string) $command->newParentId : null;
        $command->team->tenant_id = $this->calculateNewTenantId($command->team, $newParent);
        $command->team->saveQuietly(); // Avoid triggering observers

        $this->propagateTenantChange($command->team, $oldTenantId);

        $fresh = $command->team->fresh();
        assert($fresh instanceof Team, 'Team must exist after move');

        return $fresh;
    }

    /**
     * Calculate the new tenant ID based on the new parent.
     */
    private function calculateNewTenantId(Team $team, ?Team $newParent): string
    {
        if ($newParent instanceof Team) {
            return $newParent->type === TeamType::ENTERPRISE
                ? (string) $newParent->id
                : (string) $newParent->tenant_id;
        }

        if ($team->type === TeamType::ENTERPRISE) {
            // Enterprise becomes its own tenant
            return (string) $team->id;
        }

        return (string) $team->tenant_id;
    }

    /**
     * Propagate tenant changes to descendants if tenant changed.
     */
    private function propagateTenantChange(Team $team, string $oldTenantId): void
    {
        if ($team->tenant_id !== $oldTenantId) {
            $team->updateDescendantTenants((string) $team->tenant_id);
        }
    }
}
