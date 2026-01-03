<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Handlers\Commands\Users\UpdateUserProfileCommand;
use App\Handlers\Commands\Users\UpdateUserProfileHandler;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfileInformation(): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $handler = resolve(UpdateUserProfileHandler::class);
        $command = new UpdateUserProfileCommand($user, [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $result = $handler->handle($command);

        if ($result->isFailure) {
            $this->addError('email', $result->getError());
        } else {
            session()->flash('status', __('Profile updated successfully.'));
            $this->dispatch('profile-updated');
        }
    }

    public function resendVerificationNotification(): void
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return;
        }

        auth()->user()->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }

    public function render(): Factory|View
    {
        return view('livewire.settings.profile');
    }
}
