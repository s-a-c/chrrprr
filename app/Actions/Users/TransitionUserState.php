<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

final class TransitionUserState
{
    use QueueableAction;

    /**
     * Transition user state with validation and logging.
     */
    public function handle(User $user, UserState $newState, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $newState, $reason): User {
            $oldState = $user->state;

            // Validate transition (can add business logic here)
            // For now, allow any transition

            $user->state = $newState;
            $user->save();

            // Log the state transition
            Log::info('User state transitioned', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'old_state' => $oldState->value,
                'new_state' => $newState->value,
                'reason' => $reason,
            ]);

            return $user->fresh();
        });
    }
}
