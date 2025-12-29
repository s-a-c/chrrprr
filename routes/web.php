<?php

declare(strict_types=1);

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\DeleteUserForm;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Laravel\Fortify\Features;

Route::get('/', fn (): View|\Illuminate\Contracts\View\View => view('welcome'))->name('home');

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

// Public Livewire SFC route
Route::livewire('/chrrps', 'chrrps.index');

Route::middleware(['auth'])->group(function (): void {
    // Livewire SFC routes
    Route::livewire('/teams', 'teams.index')->name('teams.index');
    Route::livewire('/teams/create', 'teams.create')->name('teams.create');
    Route::livewire('/teams/{ulid}', 'teams.ulid')->name('teams.edit');
    Route::livewire('/users/{ulid}', 'users.ulid')->name('users.show');

    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(when(
            Features::canManageTwoFactorAuthentication()
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
            ['password.confirm'],
            [],
        ))->name('two-factor.show');

    Route::get('settings/delete-account', DeleteUserForm::class)->name('delete-account.show');
});
