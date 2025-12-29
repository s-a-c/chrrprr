<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Exceptions\OptimisticLockingException;
use App\Models\Team;
use App\Support\Validation\TeamNameValidator;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTeam
{
    public function __construct(
        private MoveTeam $moveTeamAction,
        private TeamNameValidator $nameValidator,
    ) {}

    /**
     * Update a team, detecting moves and delegating appropriately.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data): Team {
            $this->checkOptimisticLock($team, $data);
            $this->handleMoveIfNeeded($team, $data);
            $this->handleTranslatableFields($team, $data);
            $this->handleStandardFields($team, $data);

            return $team->fresh();
        });
    }

    /**
     * Check optimistic locking if lock_version is provided.
     */
    private function checkOptimisticLock(Team $team, array $data): void
    {
        if (isset($data['lock_version'])) {
            $currentDbVersion = (int) DB::table('teams')->where('id', $team->id)->value('lock_version');
            throw_if($currentDbVersion !== (int) $data['lock_version'], OptimisticLockingException::class);
        }
    }

    /**
     * Handle team move if parent_id is changing.
     */
    private function handleMoveIfNeeded(Team $team, array &$data): void
    {
        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== $team->parent_id) {
            $this->moveTeamAction->handle($team, $data['parent_id']);
            unset($data['parent_id']); // Remove so we don't double-update
        }
    }

    /**
     * Handle translatable fields (name, bio).
     */
    private function handleTranslatableFields(Team $team, array &$data): void
    {
        $locale = app()->getLocale();

        if (isset($data['name'])) {
            $team->setTranslation('name', $locale, $data['name']);
            $this->nameValidator->validateUnique($team);
            unset($data['name']);
        }

        if (isset($data['bio'])) {
            $team->setTranslation('bio', $locale, $data['bio']);
            unset($data['bio']);
        }
    }

    /**
     * Handle standard (non-translatable) fields.
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
