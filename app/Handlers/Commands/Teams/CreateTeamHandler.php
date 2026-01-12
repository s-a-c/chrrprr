<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Contracts\CommandHandler;
use App\Enums\TeamType;
use App\Events\Teams\TeamCreated;
use App\Handlers\BaseHandler;
use App\Models\Team;
use App\Support\Result;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Override;
use Thunk\Verbs\Facades\Verbs;

/**
 * Create Team Command Handler.
 *
 * Handles team creation using the monadic CQRS pattern with Verbs event sourcing.
 * Migrated from App\Actions\Teams\CreateTeam.
 *
 * Note: This handler operates in tenant context for most team types (Organization,
 * Division, etc.). When creating Enterprise teams, it operates in central context
 * as Enterprise creation happens before tenant context is initialized. The handler
 * properly calculates and sets tenant_id based on the parent hierarchy.
 */
final class CreateTeamHandler extends BaseHandler implements CommandHandler
{
    /**
     * Handle the CreateTeam command.
     *
     * Note: Tenant context is assumed to be initialized via middleware for tenant-scoped
     * operations. For Enterprise creation, this operates in central context.
     *
     * @param  CreateTeamCommand  $command  The command to handle
     */
    #[Override]
    public function handle(object $command): Result
    {
        if (! $command instanceof CreateTeamCommand) {
            return Result::failure('Invalid command type', ['Expected CreateTeamCommand']);
        }

        /** @return Result<Team> */
        try {
            return Result::try(
                fn (): Team => DB::transaction(fn (): Team => $this->createTeam($command)),
                ['Starting team creation']
            );
        } catch (ValidationException $e) {
            // Convert ValidationException to Result::failure for better error handling
            $messages = $e->errors();
            $firstMessage = collect($messages)->flatten()->first();
            $errorMessage = is_string($firstMessage) ? $firstMessage : 'Validation failed';

            return Result::failure($errorMessage, ['Validation exception caught']);
        }
    }

    /**
     * Create the team within a transaction, firing Verbs event for audit trail.
     *
     * Note: Tenant ID is calculated from parent hierarchy. Enterprise teams have
     * tenant_id set to their own ID (self-referential). Child teams inherit
     * tenant_id from their parent Enterprise.
     *
     * @param  CreateTeamCommand  $command  The command
     */
    private function createTeam(CreateTeamCommand $command): Team
    {
        /** @var TeamType */
        $type = $command->data['type'] instanceof TeamType
            ? $command->data['type']
            : TeamType::from((string) $command->data['type']);

        /** @var string|int|null $parentId */
        $parentId = $command->data['parent_id'] ?? null;

        // Resolve Parent for tenant calculation (use withoutGlobalScopes to access parent)
        $parent = $parentId !== null
            ? Team::query()->withoutGlobalScopes()->find($parentId)
            : null;

        // Validate hierarchy rules
        $hierarchyResult = TeamHierarchyValidator::validate($type, $parent);
        if ($hierarchyResult->isFailure) {
            throw ValidationException::withMessages([
                'parent_id' => [$hierarchyResult->error],
            ]);
        }

        // Fire Verbs event - validates hierarchy rules via event's validate() method
        // If validation fails, EventNotValid exception is thrown and caught by Result::try()
        $parentIdInt = $parentId !== null ? (int) $parentId : null;
        $state = isset($command->data['state']) && is_string($command->data['state']) ? $command->data['state'] : null;
        $status = isset($command->data['status']) && is_string($command->data['status']) ? $command->data['status'] : null;

        TeamCreated::fire(
            type: $type,
            name: $command->data['name'] ?? '',
            bio: $command->data['bio'] ?? null,
            parent_id: $parentIdInt,
            tenant_id: null, // Will be calculated below or handled by projection
            state: $state,
            status: $status,
        );

        // Commit the event to persist it to verb_events table
        Verbs::commit();

        // Determine Tenant (Enterprise logic)
        $tenantId = null;
        if ($parent !== null) {
            /** @var string|int|null $parentIdValue */
            $parentIdValue = $parent->getAttribute('id');
            $tenantId = $parent->type === TeamType::ENTERPRISE
                ? $parentIdValue
                : $parent->tenant_id;
        }

        if (! $parent && $type === TeamType::ENTERPRISE) {
            $tenantId = null; // Will be set to self ID after creation
        }

        // Create team (observer will validate hierarchy and unique name)
        // ValidationException from model events will be caught by Result::try() wrapper
        $team = Team::query()->create([
            ...$command->data,
            'tenant_id' => $tenantId,
        ]);

        // Fix Enterprise Self-Referential Tenant ID
        if ($type === TeamType::ENTERPRISE) {
            /** @var string|int $teamId */
            $teamId = $team->getAttribute('id');
            $team->updateQuietly(['tenant_id' => $teamId]);
        }

        return $team;
    }
}
