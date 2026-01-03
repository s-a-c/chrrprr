<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class DeleteUserForm extends Component
{
    public string $password = '';

    public function deleteUser(): void
    {
        $this->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = auth()->user();

        Auth::logout();

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/');
    }

    public function render(): Factory|View
    {
        return view('livewire.settings.delete-user-form');
    }
}
