<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Fortify\PasswordValidationRules;
use App\Handlers\Commands\Users\UpdateUserProfileCommand;
use App\Handlers\Commands\Users\UpdateUserProfileHandler;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Password extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Update the user's password.
     */
    public function updatePassword(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => $this->passwordRules(),
        ]);

        $handler = resolve(UpdateUserProfileHandler::class);
        $command = new UpdateUserProfileCommand($user, [
            'password' => $this->password,
        ]);

        $result = $handler->handle($command);

        if ($result->isSuccess) {
            $this->reset(['current_password', 'password', 'password_confirmation']);
            $this->dispatch('password-updated');
            session()->flash('status', 'Password updated successfully');
        } else {
            $this->addError('password', $result->error);
        }
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.settings.password');
    }
}
