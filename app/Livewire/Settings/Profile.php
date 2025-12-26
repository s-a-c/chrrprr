<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Users\UpdateUserProfile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfileInformation(UpdateUserProfile $action): void
    {
        $user = Auth::user();
        $originalEmail = $user->email;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        // If email changed, reset verification
        $emailChanged = $originalEmail !== $this->email;
        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $updateData['email_verified_at'] = null;
        }

        // Use UpdateUserProfile action
        $updatedUser = $action->handle($user, $updateData);

        if ($emailChanged && $updatedUser instanceof MustVerifyEmail) {
            $updatedUser->sendEmailVerificationNotification();
        }

        $this->dispatch('profile-updated');
    }

    /**
     * Send an email verification notification to the user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            session()->flash('status', 'verification-link-sent');
        }
    }
}
