<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Teams;

use App\Contracts\CommandHandler;
use App\Handlers\BaseHandler;
use App\Models\Team;
use App\Support\Result;
use App\Support\Validation\TeamNameValidator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Override;
use RuntimeException;

/**
 * Update Team Command Handler.
 *
 * Handles team updates using the monadic CQRS pattern.
 * Migrated from App\Actions\Teams\UpdateTeam.
 */
final class UpdateTeamHandler extends BaseHandler implements CommandHandler
{
    public function __construct(
        private readonly MoveTeamHandler $moveTeamHandler,
        private readonly TeamNameValidator $nameValidator
    ) {}

    /**
     * Handle the UpdateTeam command.
     *
     * @param  UpdateTeamCommand  $command  The command to handle
     */
    #[Override]
    public function handle(object $command): Result
    {
        if (! $command instanceof UpdateTeamCommand) {
            return Result::failure('Invalid command type', ['Expected UpdateTeamCommand']);
        }

        // Check optimistic locking first
        $lockCheck = $this->checkOptimisticLock($command->team, $command->data);
        if ($lockCheck->isFailure) {
            return $lockCheck;
        }

        // Handle move if needed using flatMap
        return $this->handleMoveIfNeeded($command, $command->data)
            ->flatMap(
                // Handle translatable fields using flatMap

                fn (mixed $data): Result => $this->handleTranslatableFields($command->team, is_array($data) ? $data : throw new RuntimeException('Invalid data type'))
                    ->flatMap(function (mixed $data) use ($command): Result {
                        assert(is_array($data), 'Data must be an array');
                        // Handle standard fields and save
                        $this->handleStandardFields($command->team, $data);

                        // Ensure team was found after update
                        return $this->ensureFound(
                            $command->team->fresh(),
                            'Team not found after update'
                        );
                    }));
    }

    /**
     * Check optimistic locking if lock_version is provided.
     *
     * @param  array<string, mixed>  $data
     * @return Result<bool>
     */
    private function checkOptimisticLock(Team $team, array $data): Result
    {
        if (! isset($data['lock_version'])) {
            return Result::success(true, ['No lock version provided']);
        }

        $lockVersion = $data['lock_version'];
        $currentDbVersion = (int) DB::table('teams')->where('id', $team->id)->value('lock_version');
        $providedVersion = is_numeric($lockVersion) ? (int) $lockVersion : throw new InvalidArgumentException('lock_version must be numeric');

        return $this->guard(
            $currentDbVersion === $providedVersion,
            'Optimistic locking failed: version mismatch'
        );
    }

    /**
     * Handle team move if parent_id is changing.
     *
     * @param  array<string, mixed>  $data
     * @return Result<array<string, mixed>>
     */
    private function handleMoveIfNeeded(UpdateTeamCommand $command, array $data): Result
    {
        if (! array_key_exists('parent_id', $data) || $data['parent_id'] === $command->team->parent_id) {
            return Result::success($data, ['No move needed']);
        }

        $parentId = $data['parent_id'];
        $moveCommand = new MoveTeamCommand(
            team: $command->team,
            newParentId: $parentId !== null ? (is_numeric($parentId) ? (int) $parentId : throw new InvalidArgumentException('parent_id must be numeric')) : null
        );

        // Use flatMap to chain the move handler result
        return $this->moveTeamHandler->handle($moveCommand)
            ->flatMap(static function () use ($data): Result {
                // Remove parent_id from data so we don't double-update
                unset($data['parent_id']);

                return Result::success($data, ['Team move handled']);
            });
    }

    /**
     * Handle translatable fields (name, bio).
     *
     * @param  array<string, mixed>  $data
     * @return Result<array<string, mixed>>
     */
    private function handleTranslatableFields(Team $team, array $data): Result
    {
        $locale = app()->getLocale();

        if (isset($data['name'])) {
            $team->setTranslation('name', $locale, $data['name']);

            // Use flatMap for name validation
            return $this->nameValidator->validateUnique($team)
                ->flatMap(static function () use ($team, $data, $locale): Result {
                    unset($data['name']);

                    if (isset($data['bio'])) {
                        $team->setTranslation('bio', $locale, $data['bio']);
                        unset($data['bio']);
                    }

                    return Result::success($data, ['Translatable fields handled']);
                });
        }

        if (isset($data['bio'])) {
            $team->setTranslation('bio', $locale, $data['bio']);
            unset($data['bio']);
        }

        return Result::success($data, ['Translatable fields handled']);
    }

    /**
     * Handle standard (non-translatable) fields.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleStandardFields(Team $team, array $data): void
    {
        if ($data !== []) {
            $team->update($data);

            return;
        }

        $team->save();
    }
}
