<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\QueueableAction\QueueableAction;
use Stevebauman\Purify\Facades\Purify;

final class UpdateUserProfile
{
    use QueueableAction;

    /**
     * Update user profile with bio sanitization.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): User
    {
        return DB::transaction(static function () use ($user, $data): ?User {
            // Handle translatable bio field
            $locale = app()->getLocale();
            if (isset($data['bio'])) {
                // Sanitize bio before storing
                $sanitized = Purify::clean($data['bio']);
                $user->setTranslation('bio', $locale, $sanitized);
                unset($data['bio']);
            }

            // Handle password hashing if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Handle email_verified_at reset if email changed
            $shouldResetEmailVerification =
                array_key_exists('email_verified_at', $data) && $data['email_verified_at'] === null;
            $emailChanged = isset($data['email']) && $user->email !== $data['email'];

            if ($shouldResetEmailVerification || $emailChanged) {
                unset($data['email_verified_at']);
            }

            // Update other fields
            if ($data !== []) {
                $user->update($data);
            }

            // Reset email verification if email changed
            if ($shouldResetEmailVerification || $emailChanged) {
                $user->email_verified_at = null;
                $user->save();
            }

            return $user->fresh();
        });
    }
}
