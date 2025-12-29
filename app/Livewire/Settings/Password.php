<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\Users\UpdateUserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

final class Password extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(UpdateUserProfile $updater): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
        ]);

        $updater->handle($user, [
            'password' => $this->password,
        ]);

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        $this->dispatch('password-updated');
    }
}
