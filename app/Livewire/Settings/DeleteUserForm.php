<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

final class DeleteUserForm extends Component
{
    public string $password = '';

    /**
     * Render the component.
     *
     * Explicitly specify the view path to work with component aliases.
     */
    public function render(): View
    {
        return view('livewire.settings.delete-user-form');
    }

    /**
     * Delete the currently authenticated user.
     *
     * Note: User deletion is protected by UserObserver which prevents
     * deletion of users with key roles. The observer handles the protection,
     * so we can call delete() directly. If more complex deletion logic is
     * needed in the future, we can create a DeleteUser action class.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        // User deletion is protected by UserObserver
        // If user has key roles, CannotDeleteKeyUserException will be thrown
        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
