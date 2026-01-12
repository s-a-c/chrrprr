<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Users;

use App\Contracts\CommandHandler;
use App\Handlers\BaseHandler;
use App\Models\User;
use App\Support\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Override;
use RuntimeException;
use Stevebauman\Purify\Facades\Purify;

/**
 * Update User Profile Command Handler.
 *
 * Handles user profile updates using the monadic CQRS pattern.
 * Migrated from App\Actions\Users\UpdateUserProfile.
 */
final class UpdateUserProfileHandler extends BaseHandler implements CommandHandler
{
    /**
     * Handle the UpdateUserProfile command.
     *
     * @param  UpdateUserProfileCommand  $command  The command to handle
     */
    #[Override]
    public function handle(object $command): Result
    {
        if (! $command instanceof UpdateUserProfileCommand) {
            return Result::failure('Invalid command type', ['Expected UpdateUserProfileCommand']);
        }

        /** @return Result<User> */
        return Result::try(
            fn (): User => DB::transaction(fn (): User => $this->updateUserProfile($command)),
            ['Starting user profile update']
        );
    }

    /**
     * Update user profile with bio sanitization.
     *
     * Note: Exceptions will be caught by Result::try() and converted to Result failures.
     */
    private function updateUserProfile(UpdateUserProfileCommand $command): User
    {
        $data = $command->data;
        $locale = app()->getLocale();

        // Handle translatable bio field
        if (isset($data['bio'])) {
            // Sanitize bio before storing
            $bio = $data['bio'];
            $sanitized = Purify::clean(is_string($bio) || is_array($bio) ? $bio : (string) $bio);
            $command->user->setTranslation('bio', $locale, $sanitized);
            unset($data['bio']);
        }

        // Handle password hashing if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make((string) $data['password']);
        }

        // Handle email_verified_at reset if email changed
        $shouldResetEmailVerification =
            array_key_exists('email_verified_at', $data) && $data['email_verified_at'] === null;
        $emailChanged = isset($data['email']) && $command->user->email !== $data['email'];

        if ($shouldResetEmailVerification || $emailChanged) {
            unset($data['email_verified_at']);
        }

        // Update other fields
        if ($data !== []) {
            $command->user->update($data);
        }

        // Reset email verification if email changed
        if ($shouldResetEmailVerification || $emailChanged) {
            $command->user->email_verified_at = null;
            $command->user->save();
        }

        $fresh = $command->user->fresh();
        throw_unless($fresh instanceof User, RuntimeException::class, 'User not found after update');

        return $fresh;
    }
}
