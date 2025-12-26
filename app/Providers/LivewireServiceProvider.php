<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Settings\DeleteUserForm;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Register Livewire component aliases to work around Livewire 4's
     * limitation with dots in nested component names.
     *
     * Note: When registering aliases, Livewire will look for view files
     * based on the alias name. Since DeleteUserForm uses a view file at
     * `livewire/settings/delete-user-form.blade.php`, we need to ensure
     * the alias matches the view path structure, or we can use the
     * fully qualified class name which will automatically resolve the view.
     */
    public function boot(): void
    {
        // Register component alias without dots for nested component usage
        // Using a simpler alias name to avoid validation issues
        Livewire::component('delete-user-form', DeleteUserForm::class);
    }
}
