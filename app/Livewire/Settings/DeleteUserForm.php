<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class DeleteUserForm extends Component
{
    public string $password = '';

    public function deleteUser(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'current_password:web'],
        ]);

        Auth::logout();

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/');
    }
}
