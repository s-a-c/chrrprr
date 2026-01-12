<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Handlers\Commands\Users\UpdateUserProfileCommand;
use App\Handlers\Commands\Users\UpdateUserProfileHandler;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfileInformation(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $handler = resolve(UpdateUserProfileHandler::class);
        $command = new UpdateUserProfileCommand($user, [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $result = $handler->handle($command);

        if ($result->isSuccess) {
            $this->dispatch('profile-updated');
            session()->flash('status', 'Profile updated successfully');
        } else {
            $this->addError('email', $result->error);
        }
    }

    /**
     * Resend the email verification notification.
     */
    public function resendVerificationNotification(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.settings.profile');
    }
}
