<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Exceptions\OptimisticLockingException;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateTeam
{
    /**
     * Update a team, detecting moves and delegating appropriately.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data): Team {
            // Manual Optimistic Lock Check
            if (isset($data['lock_version'])) {
                $currentDbVersion = (int) DB::table('teams')->where('id', $team->id)->value('lock_version');
                if ($currentDbVersion !== (int) $data['lock_version']) {
                    throw new OptimisticLockingException();
                }
            }

            // If parent_id is changing, delegate to MoveTeam action
            if (array_key_exists('parent_id', $data) && $data['parent_id'] !== $team->parent_id) {
                app(MoveTeam::class)->handle($team, $data['parent_id']);
                unset($data['parent_id']); // Remove so we don't double-update
            }

            // Handle translatable fields
            $locale = app()->getLocale();
            if (isset($data['name'])) {
                $team->setTranslation('name', $locale, $data['name']);
                $team->validateUniqueName();
                unset($data['name']);
            }
            if (isset($data['bio'])) {
                $team->setTranslation('bio', $locale, $data['bio']);
                unset($data['bio']);
            }

            // Standard Update (for non-translatable fields)
            if (! empty($data)) {
                $team->update($data);
            } else {
                $team->save();
            }

            return $team->fresh();
        });
    }
}
