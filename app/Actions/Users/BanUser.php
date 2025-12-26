<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

final class BanUser
{
    use QueueableAction;

    /**
     * Ban a user with side effects (revoke tokens, sessions).
     */
    public function handle(User $user, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $reason): User {
            // Set user state to inactive (banned)
            $user->state = UserState::INACTIVE;
            $user->save();

            // Revoke all tokens (if using Sanctum/Passport)
            $user->tokens()->delete();

            // Log the ban action
            Log::info('User banned', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'reason' => $reason,
            ]);

            return $user->fresh();
        });
    }
}
